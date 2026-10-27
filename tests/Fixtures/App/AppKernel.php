<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App;

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Wexample\SymfonyDesignSystem\WexampleSymfonyDesignSystemBundle;
use Wexample\SymfonyForms\WexampleSymfonyFormsBundle;
use Wexample\SymfonyLoader\WexampleSymfonyLoaderBundle;
use Wexample\SymfonyPreference\WexampleSymfonyPreferenceBundle;
use Wexample\SymfonyRouting\WexampleSymfonyRoutingBundle;
use Wexample\SymfonySecurity\WexampleSymfonySecurityBundle;
use Wexample\SymfonyTemplate\WexampleSymfonyTemplateBundle;
use Wexample\SymfonyTesting\Tests\Fixtures\AbstractFixtureKernel;
use Wexample\SymfonyTranslations\WexampleSymfonyTranslationsBundle;

class AppKernel extends AbstractFixtureKernel
{
    protected function getFixtureDir(): string
    {
        return __DIR__;
    }

    protected function getExtraBundles(): iterable
    {
        return [
            new WexampleSymfonyRoutingBundle(),
            new WexampleSymfonyTemplateBundle(),
            new WexampleSymfonyTranslationsBundle(),
            new WexampleSymfonyLoaderBundle(),
            new WexampleSymfonyPreferenceBundle(),
            new WexampleSymfonyDesignSystemBundle(),
            new WexampleSymfonySecurityBundle(),
            new WexampleSymfonyFormsBundle(),
        ];
    }

    protected function getConfigFiles(): array
    {
        return [
            __DIR__ . '/config/config.yaml',
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@WexampleSymfonyFormsBundle/Resources/config/routes.yaml');
    }
}
