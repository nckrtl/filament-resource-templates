<?php

namespace NckRtl\FilamentResourceTemplates;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use NckRtl\FilamentResourceTemplates\Commands\FilamentResourceTemplatesCommand;

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
