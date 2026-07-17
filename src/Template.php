<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema as IlluminateSchema;
use Illuminate\Support\Str;
use LogicException;
use NckRtl\FilamentResourceTemplates\Contracts\HasTemplateProperties;
use NckRtl\FilamentResourceTemplates\Traits\HasPublicProperties;
use Spatie\LaravelData\Data;

abstract class Template extends Data implements HasTemplateProperties
{
    use HasPublicProperties;

    /** @var class-string */
    public static string $filamentResource;

    /** @return array<int, Component> */
    public static function schema(): array
    {
        return [];
    }

    /** @return array<int, class-string<SectionTemplate>> */
    public static function sections(): array
    {
        return [];
    }

    public static function key(): string
    {
        return Str::snake(class_basename(static::class));
    }

    public static function label(): string
    {
        return Str::headline(static::key());
    }

    /** @param array<int, class-string<Template>> $templates */
    public static function form(Schema $schema, array $templates): Schema
    {
        $components = static::getTemplateSchemas($templates);

        if (count($templates) > 1) {
            array_unshift(
                $components,
                Select::make('template')
                    ->columnSpanFull()
                    ->live()
                    ->required()
                    ->options(static::getTemplates($templates))
                    ->default($templates[0])
                    ->disabled(fn (?Model $record): bool => $record?->exists === true),
            );
        }

        return $schema->components($components);
    }

    /** @return array<int, Component> */
    public static function templateSchema(string $template): array
    {
        $schema = $template::schema();

        foreach ($template::sections() as $section) {
            foreach ($section::schema() as $component) {
                $schema[] = static::rebuildWithPrefixedKeys($component, $section::key());
            }
        }

        return $schema;
    }

    /**
     * @param  array<int, class-string<Template>>  $templates
     * @return array<class-string<Template>, string>
     */
    public static function getTemplates(array $templates): array
    {
        $templateList = [];

        foreach ($templates as $template) {
            $templateList[$template] = $template::label();
        }

        return $templateList;
    }

    /**
     * @param  array<int, class-string<Template>>  $templates
     * @return array<int, Group>
     */
    public static function getTemplateSchemas(array $templates): array
    {
        $hasMultipleTemplates = count($templates) > 1;

        return array_map(
            fn (string $template): Group => Group::make(static::templateSchema($template))
                ->columnSpanFull()
                ->statePath('temp_data.'.$template::key())
                ->visible(
                    $hasMultipleTemplates
                        ? fn (Get $get): bool => $get('template') === $template
                        : true,
                ),
            $templates,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateFormDataBeforeCreateOrUpdate(array $data): array
    {
        $template = $data['template'] ?? null;

        if (! is_string($template) || ! is_subclass_of($template, self::class)) {
            throw new LogicException('A valid resource template is required.');
        }

        $temporaryData = $data['temp_data'][$template::key()] ?? null;

        if (! is_array($temporaryData)) {
            throw new LogicException("Template form data for [{$template}] is missing.");
        }

        $groupedData = $template::beforeCreateOrUpdate(
            static::groupFilamentData($temporaryData, $template::sections()),
        );
        $templateInstance = $template::from($groupedData);
        $templateData = $templateInstance->toArray();
        $modelColumns = self::templateModelColumns($template);

        return [
            'template' => $template,
            'data' => self::sift(Arr::except($templateData, $modelColumns)),
            ...Arr::only($templateData, $modelColumns),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, class-string<SectionTemplate>>  $sections
     * @return array<string, mixed>
     */
    public static function groupFilamentData(array $data, array $sections = []): array
    {
        $groupedData = $data;

        foreach ($sections as $section) {
            $sectionKey = $section::key();
            $prefix = "{$sectionKey}_";

            foreach ($data as $key => $value) {
                if (! str_starts_with($key, $prefix)) {
                    continue;
                }

                unset($groupedData[$key]);
                $groupedData[$sectionKey][substr($key, strlen($prefix))] = $value;
            }
        }

        return $groupedData;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateFormDataBeforeFill(array $data): array
    {
        $template = $data['template'] ?? null;

        if (! is_string($template) || ! is_subclass_of($template, self::class)) {
            throw new LogicException('A valid resource template is required.');
        }

        $jsonData = $data['data'] ?? [];
        $jsonData = is_array($jsonData) ? $jsonData : [];
        $modelData = Arr::except($data, ['data', 'template', 'temp_data']);
        $templateData = [
            ...$jsonData,
            ...$modelData,
        ];

        return [
            ...$data,
            'data' => [],
            'temp_data' => [
                $template::key() => static::toFilamentDataRecursive($templateData),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function toFilamentDataRecursive(mixed $data, ?string $key = null): array
    {
        if (! is_iterable($data)) {
            return [];
        }

        $flattenedData = [];

        foreach ($data as $dataKey => $value) {
            $currentKey = $key === null ? (string) $dataKey : "{$key}_{$dataKey}";

            if (is_array($value)) {
                $flattenedData = [
                    ...$flattenedData,
                    ...static::toFilamentDataRecursive($value, $currentKey),
                ];

                continue;
            }

            $flattenedData[$currentKey] = $value;
        }

        return $flattenedData;
    }

    /** @param array<string, mixed> $data */
    public static function beforeCreateOrUpdate(array $data): array
    {
        return $data;
    }

    public static function rebuildWithPrefixedKeys(Component $component, string $prefix): Component
    {
        $children = $component->getDefaultChildComponents();

        if (is_array($children) && $children !== []) {
            $newComponent = clone $component;
            $newComponent->schema(array_map(
                fn (mixed $child): mixed => $child instanceof Component
                    ? static::rebuildWithPrefixedKeys($child, $prefix)
                    : $child,
                $children,
            ));

            return $newComponent;
        }

        if (! $component instanceof Field) {
            return $component;
        }

        $key = "{$prefix}_{$component->getName()}";
        $newField = clone $component;
        $newField->name($key);
        $newField->statePath($key);

        return $newField;
    }

    /**
     * @param  class-string<Template>  $template
     * @return array<int, string>
     */
    private static function templateModelColumns(string $template): array
    {
        $resource = $template::$filamentResource;
        $modelClass = $resource::getModel();
        $model = new $modelClass;
        $columns = IlluminateSchema::getColumnListing($model->getTable());

        return array_values(array_filter(
            $columns,
            function (string $column) use ($model): bool {
                $fillable = $model->getFillable();

                if ($fillable !== []) {
                    return in_array($column, $fillable, true);
                }

                return ! in_array($column, $model->getGuarded(), true);
            },
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function sift(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::sift($value);
                $data[$key] = $value;
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
