<?php

namespace NckRtl\FilamentResourceTemplates;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Template extends TemplateBase
{
    const NAME = 'Default';

    const DEFAULT_SECTIONS = [];

    const SECTIONS = [];

    const ADDITIONAL_PROPERTIES = [];

    public string $template;

    public array $content;

    final public function __construct(array $properties)
    {
        if (empty($properties)) {
            return;
        }

        foreach ($this->publicProperties() as $property) {
            $this->$property = $properties[$property] ?? null;
        }
    }

    public static function sections(): array
    {
        return array_merge(static::DEFAULT_SECTIONS, static::SECTIONS);
    }

    public function convertContent()
    {
        foreach (static::sections() as $sectionKey => $section) {
            $this->content[$sectionKey] = array_key_exists($sectionKey, $this->content)
                ? (new $section([]))::fromArray($this->content[$sectionKey])
                : (new $section([]))::fromArray([]);
        }

        return $this;
    }

    public static function fromArray($model): self
    {
        if ($model instanceof Model) {
            $model = $model->toArray();
        }

        if (! array_key_exists('content', $model)) {
            $model['content'] = [];
        }

        $model = array_filter(
            $model,
            fn ($value, $key) => in_array($key, (new static([]))->publicProperties()),
            ARRAY_FILTER_USE_BOTH
        );

        foreach (static::sections() as $sectionKey => $section) {
            if (array_key_exists($sectionKey, $model['content'])) {
                $model['content'][$sectionKey] = (new $section([]))::fromArray($model['content'][$sectionKey]);
            } else {
                $model['content'][$sectionKey] = new $section([]);
            }

            foreach ($model['content'][$sectionKey]->defaultOverrides() as $key => $defaultValue) {
                if (empty($model['content'][$sectionKey]->$key)) {
                    $model['content'][$sectionKey]->$key = $defaultValue;
                }
            }
        }

        return new static($model);
    }

    public static function fromModel($model): self
    {
        return self::fromArray($model->toArray());
    }

    public static function toFilamentData($data)
    {
        $dto = self::fromArray($data);

        $filamentData = [];

        foreach ((new static([]))->publicProperties() as $property) {
            if ($property !== 'content') {
                $filamentData[$property] = $dto->$property;
            }
        }

        foreach (static::sections() as $sectionKey => $section) {
            $sectionContent = collect($dto->content[$sectionKey]->all())->values()->filter()->toArray();
            if (! empty($sectionContent)) {

                $filamentData = array_merge($filamentData, $dto->content[$sectionKey]->toFilamentData());
            }
        }

        return $filamentData;
    }

    public static function fromFilamentData(array $data): self
    {
        $pageData = self::fromArray($data);

        $pageData->content = [];

        $groupedContent = static::groupFilamentData($data['content']);

        foreach (static::sections() as $sectionKey => $section) {
            $pageData->content[$sectionKey] = (new $section([]))::fromArray($groupedContent[$sectionKey])->clearDefaultValues();
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

    public static function templateForm(Form $form, Collection $templates): Form
    {
        return $form->schema([
            Select::make('template')
                ->reactive()
                ->options(Template::getTemplates($templates)),

            ...Template::getTemplateSchemas($templates),
        ]);
    }

    public static function schema(): array
    {
        $mainSection = ! empty(static::form()) ? [static::form()] : [];

        foreach (static::sections() as $sectionKey => $section) {
            if (! array_key_exists($sectionKey, static::DEFAULT_SECTIONS)) {
                $mainSection[] = (new $section([]))::form() ?? [];
            }
        }

        return [Grid::make(1)->schema($mainSection)];
    }

    public static function form()
    {
        return null;
    }

    public static function getTemplates(Collection $templates): Collection
    {
        return $templates->mapWithKeys(fn ($class) => [$class => $class::NAME]);
    }

    public static function getTemplateSchemas(Collection $templates): array
    {
        return $templates->map(fn ($class) => Group::make($class::schema())
            ->columnSpan(2)
            ->afterStateHydrated(fn ($component, $state) => $component->getChildComponentContainer()->fill($state))
            ->statePath('temp_content.'.static::getTemplateName($class))
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

        $data['temp_content'][Template::getTemplateName($data['template'])] = $data;
        $data['content'] = []; // Instead of unsetting, set content to an empty array

        return $data;
    }

    public static function mutateFormDataBeforeCreateOrUpdate(array $data): array
    {
        foreach ((new ($data['template'])([]))->publicProperties() as $property) {
            if (! in_array($property, ['content', 'template'])) {
                $data[$property] = $data['temp_content'][Template::getTemplateName($data['template'])][$property];
            }
        }

        $data['content'] = $data['temp_content'][Template::getTemplateName($data['template'])];

        unset($data['temp_content']);

        $data['content'] = $data['template']::fromFilamentData($data)->content;

        foreach ($data['content'] as $key => $section) {

            $data['content'][$key] = $section->all();
        }

        $data['content'] = Template::sift($data['content']);

        return $data;
    }
}
