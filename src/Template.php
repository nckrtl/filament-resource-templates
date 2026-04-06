<?php

namespace NckRtl\FilamentResourceTemplates;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as IlluminateSchema;
use NckRtl\FilamentResourceTemplates\Traits\HasPublicProperties;
use Spatie\LaravelData\Data;

abstract class Template extends Data
{
    use HasPublicProperties;

    public static string $filamentResource;

    public static function schema(): array
    {
        return [];
    }

    public static function sections(): array
    {
        return [];
    }

    public static function form(Schema $schema, array $templates): Schema
    {
        return $schema->components([
            Select::make('template')
                ->columnSpanFull()
                ->reactive()
                ->options(self::getTemplates($templates))
                ->default(array_key_first(self::getTemplates($templates))),

            ...self::getTemplateSchemas($templates),
        ]);
    }

    public static function templateSchema(string $template): array
    {
        $schema = $template::schema();

        foreach ($template::sections() as $section) {
            $schema = array_merge($schema, [self::rebuildWithPrefixedKeys($section::schema()[0], $section::key())]);
        }

        return $schema;

    }

    public static function getTemplates(array $templates): array
    {
        $templateList = [];

        foreach ($templates as $template) {
            $templateList[$template] = $template::label();
        }

        return $templateList;
    }

    public static function getTemplateSchemas(array $templates): array
    {
        return collect($templates)->map(fn ($class) => Group::make(self::templateSchema($class))
            ->columnSpan(2)
            ->afterStateHydrated(fn ($component, $state) => $component->getChildComponentContainer()->fill($state))
            ->statePath('temp_data.'.$class::key())
            ->visible(fn ($get) => $get('template') === $class)
        )->toArray();
    }

    public static function mutateFormDataBeforeCreateOrUpdate(array $data): array
    {
        $selectedTemplateData = $data['temp_data'][$data['template']::key()];

        $groupedData = self::groupFilamentData($selectedTemplateData);

        $templateInstance = $data['template']::from($groupedData);

        return [
            'template' => $data['template'],
            'data' => self::sift($templateInstance->toArray()),
            ...static::extractTemplateModelProperties($data['template'], $templateInstance),
        ];
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

    public static function extractTemplateModelProperties(string $template, self $templateInstance): array
    {
        if (! $templateInstance::$filamentResource) {
            return $templateInstance->toArray();
        }

        $pageModel = new ((new $templateInstance::$filamentResource)->getModel());
        $modelPropertiesToStoreInDatabase = collect(IlluminateSchema::getColumnListing($pageModel->getTable()))->filter(function ($column) use ($pageModel) {
            $fillable = $pageModel->getFillable();
            $guarded = $pageModel->getGuarded();

            if (count($fillable) > 0 && in_array($column, $fillable)) {
                return true;
            }

            if (count($fillable) === 0 && ! in_array($column, $guarded)) {
                return true;
            }

            return false;
        })->toArray();

        return array_filter($templateInstance->toArray(), function ($key) use ($modelPropertiesToStoreInDatabase) {
            return in_array($key, $modelPropertiesToStoreInDatabase);
        }, ARRAY_FILTER_USE_KEY);
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        $data = static::toFilamentData($data);

        $data['temp_data'][$data['template']::key()] = $data;
        $data['data'] = [];

        return $data;
    }

    public static function toFilamentData(array $data): array
    {
        return [
            'template' => $data['template'],
            ...self::toFilamentDataRecursive($data['data']),
        ];
    }

    public static function toFilamentDataRecursive(mixed $data, ?string $key = null): array
    {
        $flattenedData = [];

        foreach ($data as $dataKey => $value) {
            $currentKey = $key ? $key.'_'.$dataKey : $dataKey;

            if (is_array($value)) {
                $flattenedData = array_merge($flattenedData, self::toFilamentDataRecursive($value, $currentKey));
            } else {
                $flattenedData[$currentKey] = $value;
            }
        }

        return $flattenedData;
    }

    private static function sift(array $array, ?callable $callback = null): array
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

    public function forDisplay(): self
    {
        return $this->withDefaultValues();
    }

    public function withDefaultValues(): self
    {
        foreach ($this->publicProperties() as $property) {
            if (is_subclass_of($this->{$property}, Data::class)) {
                $this->{$property}->setDefaultValues();
            }
        }

        return $this;
    }

    public static function beforeCreateOrUpdate(array $data): array
    {
        return $data;
    }

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
}
