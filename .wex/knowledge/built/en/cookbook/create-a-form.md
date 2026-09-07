## Create a form

Five steps, of which only two are written by hand. Everything else follows from the entity name, so the same form is never named twice.

### 1. Declare the attribute on the entity

```php
#[ORM\Entity(repositoryClass: AppRepository::class)]
#[EntityForm]
class App extends AbstractEntity
```

src/Attribute/EntityForm.php takes an optional name prefixing both generated classes — `#[EntityForm('create')]` gives `CreateAppForm` and `CreateAppFormProcessor`. A bare attribute is the ordinary case: a form bound by `data_class` binds an existing record and a new one alike, so a second form is worth a class only when the *fields* differ, not when the outcome does. The attribute is repeatable, but only the first one is scaffolded; a repetition means a second form written by hand.

### 2. Let filestate write the pair

```bash
wex app::state/rectify
```

`wexample-filestate-symfony` reads the attribute off the entity source and creates two files when they are missing:

| What | Where | What is in it |
|---|---|---|
| `{Name}{Entity}Form` | `Form\` | the field list — the only real content |
| `{Name}{Entity}FormProcessor` | `Service\FormProcessor\` | `onValid()`, often a persist and a flush |

It only ever creates them. An existing file is left exactly as it is, so the field list added at step 3 is never touched again.

The processor exists even when it says almost nothing, because that is where the rule lands the day there is one — `CurrencyFormProcessor` in `wexample/symfony-money` rejects a currency code already taken, and nothing in the form could have.

### 3. Write the field list

The one part no convention supplies, though it is not arbitrary either: it follows the ORM column types, `Types::TEXT` calling for a `TextareaInputType`, a `length`-bounded string for a `TextInputType`, a nullable column for `required: false`.

```php
$builder->add(
    'description',
    TextareaInputType::class,
    [
        self::FIELD_OPTION_NAME_LABEL => true,
        self::FIELD_OPTION_NAME_REQUIRED => false,
    ]
);

$this->builderAddSubmit($builder);
```

`label => true` means "take the label from the translation domain", which is step 4.

### 4. Write the front assets

Not scaffolded, and the form renders without them only as a bare Symfony form. Three files named after the form class in snake case, under the front path — `assets/forms/` in a bundle, `front/forms/` in an application:

```
app_form.en.yml      labels, help texts, error keys, the submit label
app_form.html.twig   extends '@WexampleSymfonyLoaderBundle/bases/form.html.twig'
app_form.ts          extends '@wexample/symfony-loader/js/Class/Form'
```

The yaml mirrors the field names, plus what the processor asks for by key:

```yaml
field:
  description:
    label: Description
action:
  submit: Save
success:
  message: App saved
```

That directory is read only because the front path is registered: a bundle implements `LoaderBundleInterface::getLoaderFrontPaths()` returning `assets/`, an application declares `wexample_symfony_loader.front_paths`. The same registration is what gives a bundle's translations their `WexampleSymfonyWexBundle` prefix and an application's their `front` one.

### 5. Wire the controller

```php
#[Route('/app/{id}/edit', name: 'app_edit')]
#[EntityFormProcessor(AppFormProcessor::class, App::class)]
public function edit(FormInterface $appForm): Response
```

There is deliberately **no data resolver per entity**: loading an entity by its route id is the same code every time, so `EntityFormDataResolver` does it once for all of them and `#[EntityFormProcessor]` wires it by default. Neither the processor nor a resolver needs registering — `_instanceof` in the services file tags them.

### What is never declared

- **`getFormClass()`** — `guessFormClass()` reads only the `Service\FormProcessor` segment and the `Processor` suffix, keeping whatever stands before them, so the pair resolves in a bundle exactly as in an application.
- **`translation_domain`** — `transTypeDomain()` derives it from the class name: `Wexample\SymfonyWex\Form\AppForm` reads `WexampleSymfonyWexBundle.forms.app_form`, which is also where the yaml of step 4 sits.

`data_class` is the exception, and the form does declare it: it is what makes the processor receive the entity from `$form->getData()` instead of an array.

### A form with no entity behind it

The same, minus the attribute and minus `data_class`. Write the form extending `AbstractForm` and the processor extending `AbstractFormProcessor` under the matching name, add the three assets, and put `#[FormProcessor]` on the controller method instead of `#[EntityFormProcessor]`.
