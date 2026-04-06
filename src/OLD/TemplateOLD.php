<?php

namespace NckRtl\FilamentResourceTemplates\OLD;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TemplateOLD extends TemplateOLD
{
    const NAME = 'Default';

    const DEFAULT_SECTIONS = [];

    const SECTIONS = [];

    const ADDITIONAL_PROPERTIES = [];

    public string $template;

    /**
     * @var array<TemplateSection>
     */
    public array $data;

    final public function __construct(array $properties = [])
    {
        if (empty($properties)) {
            return;
        }

        foreach ($this->publicProperties(fullProperty: true) as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType();

            if (! $propertyType->isBuiltin() && new ($propertyType->getName()) instanceof Carbon && gettype($properties[$propertyName]) === 'string') {
                $properties[$propertyName] = Carbon::parse($properties[$propertyName]);
            }

            $this->$propertyName = $properties[$propertyName] ?? null;
        }
    }

    public static function sections(): array
    {
        return array_merge(static::DEFAULT_SECTIONS, static::SECTIONS);
    }

    public function convertData()
    {
        foreach (static::sections() as $sectionKey => $section) {
            $this->data[$sectionKey] = array_key_exists($sectionKey, $this->data)
                ? (new $section)::fromArray($this->data[$sectionKey])
                : (new $section)::fromArray();
        }

        return $this;
    }

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

    public static function forDisplay($model): self
    {
        return self::fromArray($model, forDisplay: true);
    }

    public static function fromModel($model): self
    {
        return self::fromArray($model);
    }

    public static function toFilamentData($data)
    {
        $dto = self::fromArray($data);

        $filamentData = [];

        foreach ((new static)->publicProperties() as $property) {
            if ($property !== 'data') {
                $filamentData[$property] = $dto->$property;
            }
        }

        foreach (static::sections() as $sectionKey => $section) {
            $sectionData = collect($dto->data[$sectionKey]->all())->values()->filter()->toArray();
            if (! empty($sectionData)) {

                $filamentData = array_merge($filamentData, $dto->data[$sectionKey]->toFilamentData());
            }
        }

        return $filamentData;
    }

    public static function fromFilamentData(array $data): self
    {
        $pageData = self::fromArray($data);

        $pageData->data = [];

        $groupedData = static::groupFilamentData($data['data']);

        foreach (static::sections() as $sectionKey => $section) {
            $pageData->data[$sectionKey] = (new $section)::fromArray($groupedData[$sectionKey])->clearDefaultValues();
        }

        return $pageData;
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

    public static function schema(): array
    {
        $mainSection = ! empty(static::form()) ? [static::form()] : [];

        foreach (static::sections() as $sectionKey => $section) {
            if (! array_key_exists($sectionKey, static::DEFAULT_SECTIONS)) {
                $mainSection[] = (new $section)::form() ?? [];
            }
        }

        return [Grid::make(1)->schema($mainSection)];
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
        return collect($templates)->map(fn ($class) => Group::make($class::schema())
            ->columnSpan(2)
            ->afterStateHydrated(fn ($component, $state) => $component->getChildComponentContainer()->fill($state))
            ->statePath('temp_data.'.static::getTemplateName($class))
            ->visible(fn ($get) => $get('template') === $class)
        )->toArray();
    }

    public static function getTemplateName($class): string
    {
        return Str::of($class)->afterLast('\\')->snake()->toString();
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        $data = $data['template']::toFilamentData($data);

        $data['temp_data'][TemplateOLD::getTemplateName($data['template'])] = $data;
        $data['data'] = [];

        return $data;
    }

    public static function mutateFormDataBeforeCreateOrUpdate(array $data): array
    {
        $selectedTemplate = new ($data['template'])();

        foreach ($selectedTemplate->publicProperties() as $property) {
            if (! in_array($property, ['data', 'template'])) {
                $data[$property] = $data['temp_data'][TemplateOLD::getTemplateName($data['template'])][$property] ?? null;
            }
        }

        $data['data'] = $data['temp_data'][TemplateOLD::getTemplateName($data['template'])];

        unset($data['temp_data']);

        $data['data'] = $data['template']::fromFilamentData($data)->data;

        $data = $template->mutateDataBeforeCreateOrUpdate($data);

        foreach ($data['data'] as $key => $section) {
            $data['data'][$key] = $section->all();
            $data['data'][$key] = $section->mutateDataBeforeCreateOrUpdate($data['data'][$key]);
        }

        $data['data'] = TemplateOLD::sift($data['data']);

        return $data;
    }

    public static function mutateDataBeforeCreateOrUpdate(array $data): array
    {
        return $data;
    }

    public function mutateBeforeDisplay($model): void {}
}
