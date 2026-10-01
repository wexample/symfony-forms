<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\DateInputType;
use Wexample\SymfonyForms\Form\Type\DatetimeInputType;
use Wexample\SymfonyForms\Form\Type\DisplayValueType;
use Wexample\SymfonyForms\Form\Type\EmailInputType;
use Wexample\SymfonyForms\Form\Type\EmojiPickerType;
use Wexample\SymfonyForms\Form\Type\FileInputType;
use Wexample\SymfonyForms\Form\Type\FloatType;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\OtpInputType;
use Wexample\SymfonyForms\Form\Type\PasswordInputType;
use Wexample\SymfonyForms\Form\Type\RadioInputType;
use Wexample\SymfonyForms\Form\Type\SelectInputType;
use Wexample\SymfonyForms\Form\Type\SwitchInputType;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Form\Type\TimeInputType;
use Wexample\SymfonyForms\Form\Type\UrlInputType;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Record;

/**
 * One field per input type the package ships, and the same class serving the
 * editable and the frozen rendering — which is the point of deciding it in the
 * form and not in the template: `frozen` would be a role or a state in an
 * application, and nothing of that decision reaches the twig.
 */
class RecordForm extends AbstractForm
{
    /**
     * Each field with the type it is here to exercise.
     */
    public const array FIELD_TYPES = [
        'name' => TextInputType::class,
        'email' => EmailInputType::class,
        'site' => UrlInputType::class,
        'notes' => TextareaInputType::class,
        'password' => PasswordInputType::class,
        'weight' => FloatType::class,
        'count' => NumberInputType::class,
        'dateBorn' => DateInputType::class,
        'dateSeen' => DatetimeInputType::class,
        'timeSeen' => TimeInputType::class,
        'status' => SelectInputType::class,
        'kind' => RadioInputType::class,
        'active' => SwitchInputType::class,
        'code' => OtpInputType::class,
        'mood' => EmojiPickerType::class,
    ];

    public const array STATUS_CHOICES = [
        'Active' => 'active',
        'Archived' => 'archived',
    ];

    public const array KIND_CHOICES = [
        'First' => 'first',
        'Second' => 'second',
    ];

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $frozen = $options['frozen'];
        $common = [
            self::FIELD_OPTION_NAME_LABEL => true,
            self::FIELD_OPTION_NAME_REQUIRED => false,
            'disabled' => $frozen,
        ];

        foreach (self::FIELD_TYPES as $field => $type) {
            $builder->add($field, $type, $common + match ($type) {
                SelectInputType::class => [
                    'choices' => self::STATUS_CHOICES,
                    'auto_translate_choices' => false,
                    'placeholder' => null,
                ],
                RadioInputType::class => [
                    'choices' => self::KIND_CHOICES,
                    'auto_translate_choices' => false,
                ],
                default => [],
            });
        }

        // A file field has no column behind it here: what matters is that a
        // frozen one says what it holds instead of offering a browse button.
        $builder->add('scan', FileInputType::class, $common + [
            self::FIELD_OPTION_NAME_MAPPED => false,
            // What an application holding the stored name rather than the
            // uploaded file does: the view data is then a string.
            'data_class' => null,
            'data' => 'scan.pdf',
        ]);

        // Not a field: a value shown among them.
        $builder->add('dateCreated', DisplayValueType::class, [
            self::FIELD_OPTION_NAME_LABEL => true,
            'data' => '12 March 2026',
        ]);

        $builder->add('dateActivated', DisplayValueType::class, [
            self::FIELD_OPTION_NAME_LABEL => true,
        ]);

        $this->builderAddSubmit($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Record::class,
            // The labels are asserted as the keys they are built from, so the
            // tests do not depend on a translation catalogue.
            'translation_domain' => false,
            'csrf_protection' => false,
            'frozen' => false,
        ]);

        $resolver->setAllowedTypes('frozen', 'bool');
    }
}
