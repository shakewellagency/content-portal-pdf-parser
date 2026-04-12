<?php

namespace Shakewellagency\ContentPortalPdfParser\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Shakewellagency\ContentPortalPdfParser\ServiceProviders\ParserServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ParserServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('shakewell-parser.s3', 'local');
        $app['config']->set('shakewell-parser.linearize_on_parse', true);
        $app['config']->set('shakewell-parser.qpdf_binary', 'qpdf');
        $app['config']->set('shakewell-parser.qpdf_timeout_seconds', 60);
    }
}
