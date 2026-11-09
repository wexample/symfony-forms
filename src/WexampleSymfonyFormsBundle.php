<?php

namespace Wexample\SymfonyForms;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyForms\DependencyInjection\Compiler\FormProcessorRegistryPass;
use Wexample\SymfonyHelpers\Class\AbstractBundle;
use Wexample\SymfonyHelpers\Helper\BundleHelper;
use Wexample\SymfonyHelpers\Interface\LoaderBundleInterface;

class WexampleSymfonyFormsBundle extends AbstractBundle implements LoaderBundleInterface
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new FormProcessorRegistryPass());
    }

    public static function getLoaderFrontPaths(): array
    {
        return [
            BundleHelper::getBundleCssAlias(static::class) => __DIR__ . '/../assets/',
        ];
    }
}
