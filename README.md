# Filament Resource Templates

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nckrtl/filament-resource-templates.svg?style=flat-square)](https://packagist.org/packages/nckrtl/filament-resource-templates)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nckrtl/filament-resource-templates/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nckrtl/filament-resource-templates/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nckrtl/filament-resource-templates/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nckrtl/filament-resource-templates/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nckrtl/filament-resource-templates.svg?style=flat-square)](https://packagist.org/packages/nckrtl/filament-resource-templates)

This package allows you to add the concept of templates to your Filament Resources. By utilizing DTO's to define template sections and components we can apply more structure which is useful when we need to display the content in the frontend through auto completion. This is as well beneficial for Livewire frontends as for Inertia frontends that use typescript.

## Use with cause

This package is still considered a work in progress. There are no tests yet and things are still likely to change. So use with caution as future changes might result in data loss when editing records in filament using the template feature of this package.

## Installation

You can install the package via composer:

```bash
composer require nckrtl/filament-resource-templates
```

## Usage

1. Prepare your models and tables
   Each resource you want to use templates for needs at least a template column that can contain a string (`$table->string('template')`) and content column that will contain the structered content in json format (`$table->json('content')`). By default all data of your template will be stored in the content column, but if you need data that needs to sit in their own column that's also possible.

In the example below we'll use pages as an example and we'll store the title and slug in a separate column so we can display it more easily in the table on the resource list page.

2. Create a template file. A template file is basically just a DTO:

```php
namespace App\Filament\Resources\PageResource\Pages\Default;

use App\Filament\Resources\PageResource\Pages\Partials\Sections\TextSectionData;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use NckRtl\FilamentResourceTemplates\Template;

class DefaultTemplate extends Template
{
    const NAME = 'Default';

    public string $title;

    public string $slug;

    public static function form()
    {
        return Tabs::make('Label')
            ->tabs([
                Tab::make('General')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->placeholder('Titel')
                                ->label('Titel'),
                            TextInput::make('slug')
                                ->placeholder('slug')
                                ->prefix(config('app.url').'/')
                                ->label('slug'),
                        ]),
                    ]),
            ]);
    }
}
```

Each template at least should have a name and a form definition. In addition you can add sections and properties. When you add class properties that are used in the form definition, then those properties will be stored in their own column. So make sure the columns are added to the table beforehand.

3. Add sections
   The form definition in the template is the first section being displayed. You can add additional sections by defining them with a const:

```php
const SECTIONS = [
    TextSectionData::SECTION_KEY => TextSectionData::class,
];
```

This section is a separate DTO that may look like:

```php
namespace App\Filament\Resources\PageResource\Pages\Partials\Sections;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use NckRtl\FilamentResourceTemplates\TemplateSection;

class TextSectionData extends TemplateSection
{
    const SECTION_KEY = 'text';

    public ?string $text = '';

    public static function form()
    {
        return Section::make('Content')->schema(
            [
                RichEditor::make(static::key('text'))
            ]);
    }
}
```

4. Update the class to extend TemplateResource

```php
use NckRtl\FilamentResourceTemplates\TemplateResource;

class PageResource extends TemplateResource
...
```

5. Define a list of templates:

```php
public static function getTemplateClasses(): Collection
{
    return collect([
        DefaultTemplate::class,
        ...
    ]);
}
```

6. Now when you create a record you should see the template dropdown and the first template will be selected, while showing the form definition defined in the template.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Nick Retel](https://github.com/nckrtl)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
