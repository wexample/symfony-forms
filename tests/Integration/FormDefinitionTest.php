<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Wexample\SymfonyForms\Form\Type\ColorInputType;
use Wexample\SymfonyForms\Form\Type\KeyValueInputType;
use Wexample\SymfonyForms\Form\Type\SearchSelectInputType;
use Wexample\SymfonyForms\Form\Type\SelectInputType;
use Wexample\SymfonyForms\Form\Type\TagsInputType;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\SwitchInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Service\FormDefinitionBuilder;
use Wexample\SymfonyForms\Service\FormRenderingService;
use Wexample\SymfonyForms\Service\FormSchemaLoader;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\ContactForm;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\Type\CodeLikeType;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;

/**
 * A form handed to a vue: the form as the json-schema norm describes one,
 * each field of its kind with its label, its value and its limits — and
 * checked against the norm before it leaves.
 */
class FormDefinitionTest extends KernelTestCase
{
    public function testAFieldIsHandedWithItsNameValueAndLimits(): void
    {
        self::bootKernel();
        $contact = new Contact();
        $contact->name = 'Ada';

        $props = $this->builder()->build($this->factory()->create(ContactForm::class, $contact, ['action' => '/contacts/save']));

        $this->assertSame('contact_form', $props['name']);
        $this->assertSame('/contacts/save', $props['action']);
        $this->assertSame('POST', $props['method']);
        // No csrf in the test kernel: no token, which the norm allows.
        $this->assertArrayHasKey('token', $props);

        $fields = array_column($props['fields'], null, 'name');
        $this->assertSame(['name', 'phone', 'newsletter'], array_keys($fields));
        $this->assertSame('text', $fields['name']['type']);
        $this->assertSame('Ada', $fields['name']['value']);
        $this->assertTrue($fields['name']['required']);
        $this->assertSame(40, $fields['name']['maxLength']);
        $this->assertSame('tel', $fields['phone']['type']);
        $this->assertFalse($fields['phone']['required']);
        $this->assertArrayNotHasKey('maxLength', $fields['phone']);
    }

    public function testWhatIsHandedIsAFormOfTheNorm(): void
    {
        self::bootKernel();
        $props = $this->builder()->build($this->factory()->create(ContactForm::class, new Contact(), ['action' => '/contacts/save']));

        // Validated as it is built: a form the norm refuses never reaches a vue.
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'contact form');
        $this->assertSame(['text', 'tel', 'checkbox'], array_column($props['fields'], 'type'));
    }

    public function testAChoiceIsHandedWithItsChoices(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, null, ['action' => '/pick'])
            ->add('size', SelectInputType::class, ['choices' => ['small' => 's', 'large' => 'l'], 'auto_translate_choices' => false, 'label' => 'Size'])
            ->add('notes', TextareaInputType::class, ['required' => false, 'attr' => ['rows' => 4], 'label' => 'Notes'])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'pick form');

        [$size, $notes] = $props['fields'];
        $this->assertSame('select', $size['type']);
        $this->assertSame([['label' => 'small', 'value' => 's'], ['label' => 'large', 'value' => 'l']], $size['choices']);
        $this->assertSame('textarea', $notes['type']);
        $this->assertSame(4, $notes['rows']);
    }

    public function testAKeyValueFieldIsHandedWithItsPairsInOrder(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, ['metadata' => ['lang' => 'fr', 'channel' => 'web']], ['action' => '/rule'])
            ->add('metadata', KeyValueInputType::class, ['required' => false, 'label' => 'Metadata'])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'rule form');

        [$metadata] = $props['fields'];
        $this->assertSame('key_value', $metadata['type']);
        $this->assertSame([['key' => 'lang', 'value' => 'fr'], ['key' => 'channel', 'value' => 'web']], $metadata['pairs']);
        $this->assertArrayNotHasKey('value', $metadata);
    }

    public function testATagsFieldIsHandedWithItsWordsAndWhatItOffers(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, ['stamps' => ['urgent', 'vip']], ['action' => '/message'])
            ->add('stamps', TagsInputType::class, ['required' => false, 'label' => 'Stamps', 'suggestions' => ['late'], 'suggestions_url' => '/stamps', 'tone' => 'cat-straw', 'glyph' => 'ph:bold/tag'])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'message form');

        [$stamps] = $props['fields'];
        $this->assertSame('tags', $stamps['type']);
        $this->assertSame(['urgent', 'vip'], $stamps['tags']);
        $this->assertSame([['value' => 'late', 'label' => 'late']], $stamps['suggestions']);
        $this->assertSame('/stamps', $stamps['suggestionsUrl']);
        $this->assertSame('cat-straw', $stamps['tone']);
        $this->assertSame('ph:bold/tag', $stamps['glyph']);
        $this->assertArrayNotHasKey('value', $stamps);
    }

    public function testASearchedSelectIsHandedWithItsSourceAndTheLabelOfItsValue(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, ['type' => 'conversation'], ['action' => '/message'])
            ->add('type', SearchSelectInputType::class, ['label' => 'Type', 'options_url' => '/types', 'value_label' => 'Conversation'])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'message form');

        [$type] = $props['fields'];
        $this->assertSame('search_select', $type['type']);
        $this->assertSame('conversation', $type['value']);
        $this->assertSame('/types', $type['optionsUrl']);
        $this->assertSame('Conversation', $type['valueLabel']);
        $this->assertArrayNotHasKey('options', $type);
    }

    public function testAColourIsHandedWithItsValueAndItsSwatches(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, ['tint' => '#aabbcc'], ['action' => '/theme'])
            ->add('tint', ColorInputType::class, ['label' => 'Tint', 'required' => false, 'swatches' => ['#ff0000' => 'Red']])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'theme form');

        [$tint] = $props['fields'];
        $this->assertSame('color', $tint['type']);
        $this->assertSame('#aabbcc', $tint['value']);
        $this->assertSame([['value' => '#ff0000', 'label' => 'Red']], $tint['swatches']);
    }

    public function testTheColumnsOfAFormAndAFieldTakingTheWholeLineAreHandedOn(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, null, ['action' => '/message', 'attr' => ['class' => 'form form--columns form--columns-2']])
            ->add('name', TextInputType::class, ['label' => 'Name'])
            ->add('content', TextareaInputType::class, ['label' => 'Content', 'row_attr' => ['class' => 'form--span']])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'message form');

        $this->assertSame(2, $props['columns']);
        [$name, $content] = $props['fields'];
        $this->assertArrayNotHasKey('span', $name);
        $this->assertTrue($content['span']);
    }

    public function testACodeFieldIsHandedWithItsLanguageOrTheFieldNamingIt(): void
    {
        self::bootKernel();
        $form = $this->factory()->createNamedBuilder('message', FormType::class, null, ['action' => '/message'])
            ->add('format', SelectInputType::class, ['choices' => ['text' => 'text', 'json' => 'json'], 'auto_translate_choices' => false, 'label' => 'Format'])
            ->add('content', CodeLikeType::class, ['label' => 'Content', 'language_field' => 'format', 'language_map' => ['text' => 'jinja'], 'attr' => ['rows' => 12]])
            ->add('script', CodeLikeType::class, ['label' => 'Script', 'language' => 'python'])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'message form');

        [, $content, $script] = $props['fields'];
        $this->assertSame('code', $content['type']);
        $this->assertSame('format', $content['languageField']);
        $this->assertSame(['text' => 'jinja'], $content['languageMap']);
        $this->assertSame(12, $content['rows']);
        $this->assertSame('python', $script['language']);
        $this->assertArrayNotHasKey('languageField', $script);
    }

    public function testASwitchACheckboxAndANumberAreHandedAsTheirKinds(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, null, ['action' => '/slot'])
            ->add('streaming', SwitchInputType::class, ['label' => 'Streaming', 'required' => false, 'data' => true])
            ->add('aggregate', CheckboxType::class, ['label' => 'Aggregate', 'required' => false])
            ->add('timeout', NumberInputType::class, ['label' => 'Timeout', 'required' => false, 'stepper' => true, 'attr' => ['min' => 0, 'max' => 600, 'step' => 5]])
            ->add('tokens', IntegerType::class, ['label' => 'Tokens', 'required' => false, 'data' => 512])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'slot configs form');

        [$streaming, $aggregate, $timeout, $tokens] = $props['fields'];
        $this->assertSame(['switch', true, '1'], [$streaming['type'], $streaming['checked'], $streaming['value']]);
        $this->assertSame(['checkbox', false], [$aggregate['type'], $aggregate['checked']]);
        $this->assertSame(['number', 0, 600, 5, true], [$timeout['type'], $timeout['min'], $timeout['max'], $timeout['step'], $timeout['stepper']]);
        $this->assertSame(['number', 1, '512'], [$tokens['type'], $tokens['step'], $tokens['value']]);
    }

    public function testWhatIsAttachedToAFieldIsHandedWhateverItsKind(): void
    {
        self::bootKernel();
        $form = $this->factory()->createBuilder(FormType::class, null, ['action' => '/template'])
            ->add('content', TextareaInputType::class, ['label' => 'Content', 'extensions' => ['versions' => ['url' => '/_api/template/versions/__id__']]])
            ->add('timeout', NumberInputType::class, ['label' => 'Timeout', 'extensions' => ['expression' => ['language' => 'jinja', 'pattern' => '\\{[{%]']]])
            ->add('notes', TextareaInputType::class, ['label' => 'Notes'])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'template form');

        [$content, $timeout, $notes] = $props['fields'];
        $this->assertSame(['versions' => ['url' => '/_api/template/versions/__id__']], $content['extensions']);
        $this->assertEquals(['expression' => ['language' => 'jinja', 'pattern' => '\\{[{%]', 'rows' => 2]], $timeout['extensions']);
        $this->assertArrayNotHasKey('extensions', $notes);

        // Drawn for a record: the record's id where the placeholder stood.
        $record = new class {
            public function getId(): string
            {
                return 'u1';
            }
        };
        $drawn = $this->factory()->createBuilder(FormType::class, $record, ['action' => '/template/u1'])
            ->add('content', TextareaInputType::class, ['mapped' => false, 'extensions' => ['versions' => ['url' => '/_api/template/versions/__id__']]])
            ->getForm()->createView();
        $this->assertSame(['versions' => ['url' => '/_api/template/versions/u1']], $drawn['content']->vars['extensions_drawn']);
        $this->assertSame([], $form->createView()['content']->vars['extensions_drawn']);

        // An expression goes through a number as the text it is.
        $form->submit(['timeout' => '{{ model.timeout }}', 'content' => '', 'notes' => '']);
        $this->assertSame('{{ model.timeout }}', $form->get('timeout')->getData(), (string) $form->get('timeout')->getErrors(true));
        $this->assertTrue($form->get('timeout')->isValid(), (string) $form->getErrors(true, false));

        // Held as an expression, the number shows it, and the definition hands it as its value.
        $held = $this->factory()->createBuilder(FormType::class, null, ['action' => '/slot'])
            ->add('timeout', NumberInputType::class, ['label' => 'Timeout', 'data' => '{{ model.timeout }}', 'extensions' => ['expression' => ['language' => 'jinja', 'pattern' => '\\{[{%]']]])
            ->getForm();
        $this->assertSame('{{ model.timeout }}', $this->builder()->build($held)['fields'][0]['value']);
        $plain = $this->factory()->createBuilder(FormType::class, null, ['action' => '/slot'])
            ->add('timeout', NumberInputType::class, ['label' => 'Timeout', 'data' => 30, 'extensions' => ['expression' => ['language' => 'jinja', 'pattern' => '\\{[{%]']]])
            ->getForm();
        $this->assertSame('30', $this->builder()->build($plain)['fields'][0]['value']);

        // What no extension is named for is refused.
        $this->expectException(InvalidOptionsException::class);
        $this->factory()->createBuilder(FormType::class)->add('x', TextInputType::class, ['extensions' => ['wings' => []]])->getForm();
    }

    public function testTheEntityIdInTheActionGivesWayToAPlaceholder(): void
    {
        self::bootKernel();
        $contact = new class () extends Contact {
            public function getId(): string
            {
                return '42';
            }
        };

        $form = $this->factory()->create(ContactForm::class, $contact, ['action' => '/_forms/submit/contact_form/entity/42']);

        $this->assertSame(
            '/_forms/submit/contact_form/entity/__id__',
            $this->builder()->build($form, ['id_placeholder' => '__id__'])['action']
        );
        $this->assertSame('/_forms/submit/contact_form/entity/42', $this->builder()->build($form)['action']);
    }

    public function testALayoutMakesTheFormByItsClass(): void
    {
        self::bootKernel();
        $contact = new Contact();
        $contact->name = 'Ada';

        $props = self::getContainer()->get('twig')
            ->createTemplate("{{ form_definition_for('Wexample\\\\SymfonyForms\\\\Tests\\\\Fixtures\\\\App\\\\Form\\\\ContactForm', contact)|json_encode|raw }}")
            ->render(['contact' => $contact]);
        $props = json_decode($props, true);

        $this->assertSame('contact_form', $props['name']);
        $this->assertSame('Ada', array_column($props['fields'], 'value', 'name')['name']);
    }

    private function builder(): FormDefinitionBuilder
    {
        return self::getContainer()->get(FormDefinitionBuilder::class);
    }

    private function schemas(): FormSchemaLoader
    {
        return self::getContainer()->get(FormSchemaLoader::class);
    }

    private function rendering(): FormRenderingService
    {
        return self::getContainer()->get(FormRenderingService::class);
    }

    private function factory(): FormFactoryInterface
    {
        return self::getContainer()->get('form.factory');
    }
}
