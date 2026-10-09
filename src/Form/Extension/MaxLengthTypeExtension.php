<?php

namespace Wexample\SymfonyForms\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A text field says how long it may be: `maxlength`, from the shortest
 * `Length(max:)` the field's own `constraints` or the property it is mapped to
 * declare, so the browser stops the typing the server would refuse. An
 * explicit `attr.maxlength` wins.
 */
class MaxLengthTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly ?ValidatorInterface $validator = null,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        // Email, tel, url, search, password and textarea all inherit from it.
        return [TextType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($view->vars['attr']['maxlength'])) {
            return;
        }

        $max = null;

        foreach ([...$options['constraints'], ...$this->getPropertyConstraints($form)] as $constraint) {
            if ($constraint instanceof Length && null !== $constraint->max) {
                $max = null === $max ? $constraint->max : min($max, $constraint->max);
            }
        }

        if (null !== $max) {
            $view->vars['attr']['maxlength'] = $max;
        }
    }

    /**
     * @return list<Constraint>
     */
    private function getPropertyConstraints(FormInterface $form): array
    {
        $parent = $form->getParent();
        $class = $parent?->getConfig()->getDataClass();

        if (! $this->validator || ! $class || ! $form->getConfig()->getMapped()) {
            return [];
        }

        $property = (string) ($form->getPropertyPath() ?? $form->getName());
        $metadata = $this->validator->getMetadataFor($class);

        if (! $metadata instanceof ClassMetadataInterface || ! $metadata->hasPropertyMetadata($property)) {
            return [];
        }

        $constraints = [];

        foreach ($metadata->getPropertyMetadata($property) as $propertyMetadata) {
            array_push($constraints, ...$propertyMetadata->getConstraints());
        }

        return $constraints;
    }
}
