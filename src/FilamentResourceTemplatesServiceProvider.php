<?php

namespace NckRtl\FilamentResourceTemplates;

use NckRtl\FilamentResourceTemplates\Commands\FilamentResourceTemplatesCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentResourceTemplatesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-resource-templates')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_filament-resource-templates_table')
            ->hasCommand(FilamentResourceTemplatesCommand::class);
    }
}
