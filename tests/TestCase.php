<?php

namespace NckRtl\FilamentResourceTemplates\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use NckRtl\FilamentResourceTemplates\FilamentResourceTemplatesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'NckRtl\\FilamentResourceTemplates\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            FilamentResourceTemplatesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_filament-resource-templates_table.php.stub';
        $migration->up();
        */
    }
}
