<?php

namespace Wexample\SymfonyForms\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorDataResolverInterface;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyFormsExtension extends AbstractWexampleSymfonyExtension
{
    public const string TAG_FORM_PROCESSOR = 'wexample.symfony_forms.form_processor';
    public const string TAG_FORM_PROCESSOR_DATA_RESOLVER = 'wexample.symfony_forms.form_processor_data_resolver';
    public const string FORM_THEME = '@WexampleSymfonyFormsBundle/form/form_theme.html.twig';

    public function prepend(ContainerBuilder $container): void
    {
        parent::prepend($container);

        if (! $container->hasExtension('twig')) {
            return;
        }

        $container->prependExtensionConfig('twig', [
            'form_themes' => [self::FORM_THEME],
        ]);
    }

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        $container
            ->registerForAutoconfiguration(AbstractFormProcessor::class)
            ->addTag(self::TAG_FORM_PROCESSOR);

        $container
            ->registerForAutoconfiguration(FormProcessorDataResolverInterface::class)
            ->addTag(self::TAG_FORM_PROCESSOR_DATA_RESOLVER);
    }
}
