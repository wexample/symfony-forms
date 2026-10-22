<?php

namespace Wexample\SymfonyForms\Helper;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyCheck\Checker\AbstractRequiredFieldsChecker;
use Wexample\SymfonyCheck\Class\Finding;

/**
 * Shows on a form why a gate sent the user to it: a message above the form,
 * and beside each field a finding names by its `path`, dotted into embedded
 * forms (`address.city`). The texts are the form's own; a finding other than
 * a missing field keeps the one of the package that raised it.
 */
class FormGateHelper
{
    public const string TRANSLATION_DOMAIN = 'WexampleSymfonyFormsBundle.common.gate';

    /**
     * @param list<Finding> $findings
     */
    public static function addFindingErrors(
        FormInterface $form,
        array $findings
    ): void {
        if ([] === $findings) {
            return;
        }

        $form->addError(new FormError(self::TRANSLATION_DOMAIN . '::complete_to_continue'));

        foreach ($findings as $finding) {
            $field = self::findField($form, (string) ($finding->parameters['path'] ?? ''));
            $error = AbstractRequiredFieldsChecker::CODE_FIELD_MISSING === $finding->code
                ? new FormError(self::TRANSLATION_DOMAIN . '::field_required')
                : new FormError($finding->domain . '::' . $finding->code, null, $finding->parameters);

            ($field ?? $form)->addError($error);
        }
    }

    /**
     * The child at a dotted path, null when the form has no such field: the
     * finding is then shown above the form.
     */
    private static function findField(
        FormInterface $form,
        string $path
    ): ?FormInterface {
        if ('' === $path) {
            return null;
        }

        foreach (explode('.', $path) as $name) {
            if (! $form->has($name)) {
                return null;
            }

            $form = $form->get($name);
        }

        return $form;
    }
}
