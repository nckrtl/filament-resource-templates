<?php

namespace NckRtl\FilamentResourceTemplates\OLD;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NckRtl\FilamentResourceTemplates\Traits\HasPublicProperties;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data;

class Template extends Data
{
    use HasPublicProperties;

    public static ?string $name = null;

    public static array $defaultSections = [];

    public static array $sections = [];

    public string $template;

    /**
     * @var array<SectionTemplate>
     */
    public array $data;

    public static function templateSchema(): array
    {
        $schema = static::schema();

        foreach (static::sections() as $section) {
            $schema = array_merge($schema, $section::schema());
        }

        return [Grid::make(1)->schema(array_filter($schema))];
    }

    public static function schema(): array
    {
        return [];
    }

    public static function mutateFormDataBeforeCreateOrUpdate(array $data): array
    {
        $data['data'] = $data['temp_data'][self::getTemplateName($data['template'])];
        unset($data['temp_data']);

        $data['data']['template'] = self::getTemplateName($data['template']);

        $groupedData = self::groupFilamentData($data['data']);

        $selectedTemplateInstance = new ($data['template'])();

        foreach (new ReflectionClass($data['template'])->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->class) {
                $selectedTemplateInstance->$property = $property->class::from($groupedData[$property->name]);
            } else {
                $selectedTemplateInstance->$property = $groupedData[$property->getName()] ?? null;
            }

        }

        // $data['data'] = self::fromFilamentData($data)->data;

        // ray($data);

        // $selectedTemplate = new ($data['template'])();

        // // foreach ($selectedTemplate->publicProperties() as $property) {

        // //     if (! in_array($property, ['data', 'template'])) {
        // //         ray($property);

        // //         $data[$property] = $data['temp_data'][self::getTemplateName($data['template'])][$property] ?? null;
        // //     }
        // // }

        // // ray($selectedTemplate);

        foreach ($data['data'] as $key => $section) {
            $data['data'][$key] = $section->all();
            $data['data'][$key] = $section->mutateDataBeforeCreateOrUpdate($data['data'][$key]);
        }

        $data['data'] = TemplateOLD::sift($data['data']);

        return $data;
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        $data = $data['template']::toFilamentData($data);

        $data['temp_data'][TemplateOLD::getTemplateName($data['template'])] = $data;
        $data['data'] = [];

        return $data;
    }

    // private static function addTemplateForm(array $schema): array
    // {
    //     if (empty(static::schema())) {
    //         return $schema;
    //     }

    //     $schema[] = static::schema();

    //     return $schema;
    // }

    // private static function addSectionForm(array $schema, SectionTemplate $section)
    // {
    //     if (! in_array($section, static::$defaultSections)) {
    //         $schema[] = $section::form() ?? [];
    //     }
    //     if (empty($section::form())) {
    //         return $schema;
    //     }
    // }

    public static function rebuildWithPrefixedKeys(Component $component, string $prefix): Component
    {
        // If it's a container (e.g., Section, Group, etc.)
        if (method_exists($component, 'getChildComponents') && method_exists($component, 'schema') && count($component->getChildComponents()) > 0) {
            $newComponent = clone $component;

            $children = $component->getChildComponents();

            $newSchema = array_map(function (Component $child) use ($prefix) {
                return self::rebuildWithPrefixedKeys($child, $prefix);
            }, $children);

            $newComponent->schema($newSchema);

            return $newComponent;
        }

        // If it's a Field (e.g., TextInput, Textarea, etc.)
        if ($component instanceof Field) {
            $originalName = $component->getName();
            $key = "{$prefix}_{$originalName}";

            /** @var Field $newField */
            $newField = clone $component;
            $newField->name($key);
            $newField->statePath($key);

            return $newField;
        }

        // Return untouched if it doesn’t match expected types
        return $component;
    }

    public static function form(Form $form, array $templates): Form
    {
        return $form->schema([
            Select::make('template')
                ->reactive()
                ->options(self::getTemplates($templates)),

            ...self::getTemplateSchemas($templates),
        ]);
    }

    public static function getTemplates(array $templates): array
    {
        $templateList = [];

        foreach ($templates as $template) {
            $templateList[$template] = $template::NAME;
        }

        return $templateList;
    }

    public static function getTemplateSchemas(array $templates): array
    {
        return collect($templates)->map(fn ($class) => Group::make($class::templateSchema())
            ->columnSpan(2)
            ->afterStateHydrated(fn ($component, $state) => $component->getChildComponentContainer()->fill($state))
            ->statePath('temp_data.'.static::getTemplateName($class))
            ->visible(fn ($get) => $get('template') === $class)
        )->toArray();
    }

    public static function sections(): array
    {
        return array_merge(static::$defaultSections, static::$sections);
    }

    public static function getTemplateName($class): string
    {
        return Str::of($class)->afterLast('\\')->snake()->toString();
    }

    // public static function fromFilamentData(array $data): self
    // {
    //     // $groupedData = static::groupFilamentData($data['data']);

    //     // foreach (static::sections() as $section) {
    //     //     $groupedData['data'][$section::KEY] = (new $section)::fromArray($groupedData[$section::KEY])->clearDefaultValues();
    //     //     unset($groupedData[$section::KEY]);
    //     // }

    //     // ray($groupedData);

    //     // // ray(new static()::from($groupedData));

    //     // return new static($groupedData);

    //     ray('aaaa');

    //     $pageData = self::fromArray($data);

    //     $pageData->data = [];

    //     $groupedData = static::groupFilamentData($data['data']);

    //     foreach (static::sections() as $section) {
    //         $pageData->data[$section::KEY] = (new $section)::fromArray($groupedData[$section::KEY])->clearDefaultValues();
    //     }

    //     return $pageData;
    // }

    public static function fromArray($rawModel, $forDisplay = false): self
    {
        $model = $rawModel;

        if ($model instanceof Model) {
            $model = $model->toArray();
        }

        if (! array_key_exists('data', $model)) {
            $model['data'] = [];
        }

        $model = array_filter(
            $model,
            fn ($value, $key) => in_array($key, (new static)->publicProperties()),
            ARRAY_FILTER_USE_BOTH
        );

        foreach (static::sections() as $sectionKey => $section) {
            if (array_key_exists($sectionKey, $model['data'] ?? [])) {
                $model['data'][$sectionKey] = (new $section)::fromArray($model['data'][$sectionKey]);
            } else {
                $model['data'][$sectionKey] = new $section;
            }

            if ($forDisplay) {
                $model['data'][$sectionKey]->mutateBeforeDisplay($rawModel);
            }

            foreach ($model['data'][$sectionKey]->defaultOverrides() as $key => $defaultValue) {
                if (empty($model['data'][$sectionKey]->$key)) {
                    $model['data'][$sectionKey]->$key = $defaultValue;
                }
            }
        }

        $model = new static($model);

        if ($forDisplay) {
            $model->mutateBeforeDisplay($rawModel);
        }

        return $model;
    }

    public static function groupFilamentData($data): array
    {
        return array_reduce(array_keys($data), fn ($outputArray, $key) => static::groupFilamentDataRecursive($outputArray, $key, $data[$key]), []);
    }

    public static function groupFilamentDataRecursive(array $outputArray, string $key, $value): array
    {
        if (! str_contains($key, '_')) {
            $outputArray[$key] = $value;

            return $outputArray;
        }

        $keyParts = explode('_', $key);
        $subKey = array_pop($keyParts);
        $currentArray = &$outputArray;

        foreach ($keyParts as $groupKey) {
            if (! isset($currentArray[$groupKey])) {
                $currentArray[$groupKey] = [];
            }
            $currentArray = &$currentArray[$groupKey];
        }

        $currentArray[$subKey] = $value;

        return $outputArray;
    }

    public static function sift(array $array, ?callable $callback = null): array
    {
        $callback = $callback ?? fn ($value) => empty($value);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sift($value, $callback);
            }

            if ($callback($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function mutateBeforeDisplay($model): void {}
}
