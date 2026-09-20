## Architecture

The bundle holds two layers that only meet through the form object. A **rendering** layer turns Symfony form types into design-system components (`src/Form/Type/*`, the form theme, the Twig extension). A **processing** layer takes the submission off the controller's hands (`AbstractFormProcessor`, the `#[FormProcessor]` attribute, a kernel subscriber, an argument resolver and a submit route). A form type works without a processor; a processor always names a form class.

### Bundle wiring

src/WexampleSymfonyFormsBundle.php extends `AbstractBundle` from `wexample/symfony-helpers` and implements `LoaderBundleInterface`, exposing `assets/` as a front path so the design-system loader can pick up the bundle's front assets.

src/DependencyInjection/WexampleSymfonyFormsExtension.php does three things. It prepends the form theme into the Twig configuration, so an application never has to declare it:

```php
$container->prependExtensionConfig('twig', [
    'form_themes' => [self::FORM_THEME],
]);
```

It autoconfigures the two extension points of the processing layer:

```php
$container->registerForAutoconfiguration(AbstractFormProcessor::class)
    ->addTag(self::TAG_FORM_PROCESSOR);
$container->registerForAutoconfiguration(FormProcessorDataResolverInterface::class)
    ->addTag(self::TAG_FORM_PROCESSOR_DATA_RESOLVER);
```

And it loads src/Resources/config/services.yaml, where both tags are turned into service locators injected into `FormProcessorPostHandler` and `FormProcessorRequestSubscriber` (`!tagged_locator`). src/Resources/config/routes.yaml imports the controllers by attribute.

### Rendering: types, theme, components

A type under src/Form/Type is deliberately thin — a parent type and a block prefix, no widget code:

```php
class TextInputType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }
```

`FieldOptionsTrait` (src/Form/Traits/FieldOptionsTrait.php) supplies `getBlockPrefix()` from the snake-cased short class name minus the `Type` suffix, which is why `SwitchInputType` needs no override while `FloatType` declares `form_float` explicitly.

That block prefix is the whole contract with assets/form/form_theme.html.twig, which starts from `form_div_layout.html.twig` and replaces each widget block by a call to a design-system component:


```twig
{% block switch_input_widget %}
    ...
    {{ component(
        render_pass,
        '@WexampleSymfonyDesignSystemBundle/components/form/switch-input',
        { name: full_name, id: id, label: label_value, required: required, ... }
    ) }}
{% endblock %}
```


Each widget block resolves its label the same way: a label left at `true` becomes the key `field.<name>.label` (`action.<name>` for submit and button widgets), a placeholder at `true` becomes `field.<name>.placeholder`, both translated in the form's own domain. `form_row` adds `form--group` plus `has-error`, `is-required` or `is-optional` depending on errors and the `required_mode` option.

Where that option and the translation domain come from is src/Form/AbstractForm.php:

```php
$resolver->setDefaults([
    'translation_domain' => self::transTypeDomain($this),
    'required_mode' => 'optional',
]);
```

`transTypeDomain()` cuts the class name at its `\Form\` segment: what follows becomes the domain path, snake-cased and dot-joined, and what precedes it says whose assets those are — `front` for an application, the bundle's own alias otherwise. `App\Form\WidgetForm` reads `front.forms.widget_form`, `Wexample\SymfonyWex\Form\AppForm` reads `WexampleSymfonyWexBundle.forms.app_form`; either way the domain restates the `assets/forms/` path of the yaml file. `transFormDomain()` still reads the root form's `translation_domain` option first, so a form may override the derivation. `AbstractForm` also exposes `public static bool $ajax`, pushed to the view as `vars['ajax']` and read by the processor when it computes the form action.

### Rendering without a form object

src/Twig/FormExtension.php registers one Twig function per component (`text_input`, `password_input`, `hidden_input`, `submit_input`, `textarea_input`, `select_input`) for templates that render an input without a Symfony form behind it. Each sets `$context['type']`, validates, then renders the same design-system template the theme uses. Validation lives in src/Service/FormRenderingService.php, which loads the JSON schema for the component and checks the Twig context against it:

```php
$schema = SchemaLoaderHelper::loadSchema($type);
$data = TemplateHelper::stripTwigContextKeys($context);
JsonSchemaValidationHelper::validateOrThrow($schema, $data, $type.' context');
```

### Processing: the processor

src/Service/FormProcessor/AbstractFormProcessor.php is what an application subclasses. It pairs itself with a form class by convention — `guessFormClass()` swaps the `Service\FormProcessor` segment of its own name for `Form` and drops the `Processor` suffix — and throws if the result does not exist, telling the developer to override `getFormClass()`.

It owns four groups of behaviour:

- **Building.** `createForm()` calls the factory with the resolved class and, unless one was passed, an action URL. `createFormAction()` points at the submit route when the form class has `$ajax = true`, otherwise at the current request URI.
- **Hooks.** `processSubmittedForm()` calls `onSubmitted()`, then `onValid()` or `onInvalid()` — all empty in the base class, all meant to be overridden.
- **Outcome.** `setNotification()` stores a `Notification` (message plus one of `success`/`error`/`warning`/`info`); `redirect()`, `redirectToRoute()` and `redirectToPreviousOrToRoute()` store a success action array. `redirectToPreviousOrToRoute()` reads the `redirect` request parameter, then the session's redirect and security targets, and only accepts a target passing `isSafeRedirectTarget()` — starting with `/` and not `//`.
- **Response, partially.** `handleSubmissionResponseFromForm()` returns a `RedirectResponse` for a non-JSON request that has a redirect URL, and `null` otherwise. The docblock states the boundary: *"Building the JSON payload belongs to the transport layer, which knows whether the caller expects one."* When the browser is about to reload a page, the notification is moved into the session flash bag and cleared, so no payload built afterwards can carry it twice.

Notification messages prefixed with `@form::` are rewritten by `resolveNotificationDomain()` into an absolute key built from the form's domain, which keeps them translatable outside the form domain context.

### Processing: the two entry paths

**Attribute on a controller method.** `#[FormProcessor(processorClass, formArgumentName, formDataResolverClass, formDataResolverOptions)]` is repeatable (src/Attribute/FormProcessor.php). src/EventSubscriber/FormProcessorRequestSubscriber.php listens on `KernelEvents::REQUEST` at priority 5, reflects the controller method, and for each attribute pulls the processor out of the locator. On `POST` it calls `handleSubmission()` — or `handleSubmissionWithData()` when a data resolver is declared — then lets the processor answer; if it returns nothing and the request is JSON, the subscriber builds the payload itself:

```php
$event->setResponse(new JsonResponse(
    $this->payloadBuilder->build($processor, $form)
));
```

Otherwise the built form is stored under the request attribute `_form_processor_forms`, keyed by `formArgumentName`. src/ArgumentResolver/FormProcessorValueResolver.php then hands it to the controller argument: it only supports arguments typed `FormInterface` whose name matches a `formArgumentName` on the same method, and yields the form from that attribute. The controller therefore receives a form already created, already submitted, already validated.

**Direct POST to the submit route.** src/Controller/FormController.php exposes `_forms/submit/{name}` (route `form_processor_submit`, matching `AbstractFormProcessor::FORM_SUBMIT_ROUTE`) and `_forms/submit/{name}/entity/{id}`, both POST only, both delegating to src/Service/FormProcessor/FormProcessorPostHandler.php. The handler walks the tagged locator to find the processor whose form matches the URL name:

```php
if (ClassHelper::longTableized($processorClass::getFormClass()) === $formName) {
```

then checks `getRequiredRoles()` — `['ROLE_USER']` by default, `RoleHelper::PUBLIC_ACCESS` opening it up — and throws `AccessDeniedHttpException` when none is granted. It returns the processor's redirect if there is one, a `JsonResponse` for a JSON request, and `204 No Content` otherwise.

### The JSON payload

src/Service/FormProcessor/FormResponsePayload.php extends `AdaptiveResponse` from `wexample/symfony-loader` with `responseType = 'form'`, and carries the form name, the errors, the notification and the action (`redirect`, `reload`, `default`, `embed_stay`, `embed_redirect`). `setErrors()` sets `$this->ok` from the error count.

src/Service/FormProcessor/FormResponsePayloadBuilder.php fills it. `collectErrors()` splits errors between the root form and its fields, keying field errors by `FormHelper::buildFullFieldName($origin)` — the bracketed HTML name, `form[child][field]`, so the front end can match them to inputs. It also collects the raw messages as translation keys, then translates them inside the form's domain, pushing and reverting it around the loop:

```php
$this->translator->setDomain(Translator::DOMAIN_TYPE_FORM, ...);
...
$this->translator->revertDomain(Translator::DOMAIN_TYPE_FORM);
```

Both transports — subscriber and post handler — call this same builder.

### Form data resolvers

A processor whose form needs data built from the request declares a resolver implementing src/Service/FormProcessor/FormProcessorDataResolverInterface.php, a single `resolve(Request $request, array $options = []): mixed`. The subscriber calls it before creating the form and passes the result to `createForm()` or `handleSubmissionWithData()`.

The bundle ships two. `EntityEditFormDataResolver` reads the `id` route attribute and returns an `EntityEditFormData` — entity type, secure id, plus mutable `formData` and `entity` slots the processor fills in. `#[ApiEntityFormProcessor]` (src/Attribute/ApiEntityFormProcessor.php) is a `FormProcessor` pre-wired to it: it defaults the resolver, injects `'entityType' => $processorClass::getEntityClass()` into the options, and guesses the argument name from the form's short name, turning a `…EntityForm` suffix into `…Form` and lowercasing the first letter. Note that `getEntityClass()` is called on the processor class but not declared by `AbstractFormProcessor` — a processor used with this attribute must provide it.

### Editing an entity: the convention

An entity edited through a form declares `#[EntityForm]` (src/Attribute/EntityForm.php), and the two classes that follow are named after it rather than chosen: `{Name}{Entity}Form` under `Form\` holds the field list, `{Name}{Entity}FormProcessor` under `Service\FormProcessor\` holds `onValid()`. Neither is written by hand — `wexample-filestate-symfony` reads the attribute off the entity source and creates both when they are missing, never touching a file that already exists.

Three things make the pair work, and all three are silences: the form declares `data_class` and nothing else, `getFormClass()` is never declared because `guessFormClass()` keeps whatever stands before the `Service\FormProcessor` segment, and `translation_domain` is never declared because `transTypeDomain()` derives it the same way. There is likewise no data resolver per entity: `EntityFormDataResolver` loads by route id once for all of them, and `#[EntityFormProcessor]` wires it by default.

The step-by-step recipe, front assets included, is in the cookbook: `create-a-form`. What the attribute makes filestate write, next to every other entity attribute, is in `wexample-filestate-symfony`, cookbook `create-an-entity`.

### Adding to the bundle

A new field type means two edits that must agree: a class in src/Form/Type using `FieldOptionsTrait`, and a `<prefix>_widget` block in assets/form/form_theme.html.twig rendering the matching design-system component. The prefix is the link — get it wrong and Symfony silently falls back to `form_div_layout`. A new Twig function additionally needs a `FORM_TYPE_*` constant in `FormRenderingService` and a schema resolvable by `SchemaLoaderHelper::loadSchema()`.

A new processor needs no registration: `_instanceof` in the services file tags every `AbstractFormProcessor` subclass, and the same holds for resolvers implementing `FormProcessorDataResolverInterface`.
