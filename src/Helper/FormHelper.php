<?php

namespace Wexample\SymfonyForms\Helper;

use Symfony\Component\Form\FormInterface;

class FormHelper
{
    public static function buildFullFieldName(FormInterface $field): string
    {
        $parts = [];
        $current = $field;

        while ($current) {
            $name = $current->getName();
            if ($name !== '') {
                array_unshift($parts, $name);
            }
            $current = $current->getParent();
        }

        $root = array_shift($parts) ?? '';
        $full = $root;

        foreach ($parts as $part) {
            $full .= '[' . $part . ']';
        }

        return $full;
    }
}
