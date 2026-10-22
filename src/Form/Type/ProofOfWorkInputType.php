<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;
use Wexample\SymfonySecurity\Service\ProofOfWorkService;

/**
 * The captcha of a form open to anyone, with nothing to solve by hand: the
 * browser works out the challenge drawn with the page while the form is
 * being filled in (symfony-security's ProofOfWorkService), and holds the
 * submission back the moment it takes when sent sooner.
 *
 * Beside it, a field hidden from people and offered to programs, which fill
 * in whatever they find: a value there is a program's.
 *
 * Refused, the error is the form's own, above it. A solution sends one
 * submission: a form answered by its page draws a new challenge with it.
 */
class ProofOfWorkInputType extends AbstractType
{
    use FieldOptionsTrait;

    public const string FIELD_SOLUTION = 'solution';

    public const string FIELD_TRAP = 'website';

    public const string TRANSLATION_DOMAIN = 'WexampleSymfonyFormsBundle.common.proof_of_work';

    public function __construct(
        private readonly ProofOfWorkService $proofOfWork,
    ) {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add(self::FIELD_SOLUTION, HiddenType::class)
            ->add(self::FIELD_TRAP, TextType::class, ['required' => false])
            ->addEventListener(FormEvents::POST_SUBMIT, $this->onPostSubmit(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'label' => false,
            'error_bubbling' => true,
        ]);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars['challenge'] = $this->proofOfWork->issue();
    }

    private function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();

        if ('' !== (string) $form->get(self::FIELD_TRAP)->getData()
            || ! $this->proofOfWork->verify($form->get(self::FIELD_SOLUTION)->getData())) {
            $form->addError(new FormError(self::TRANSLATION_DOMAIN . '::failed'));
        }
    }
}
