<?php

namespace NckRtl\FilamentResourceTemplates;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;

class TemplateSection extends TemplateBase
{
    const SECTION_KEY = '';

    final public function __construct(array $properties)
    {
        foreach ($properties as $propertyName => $propertyValue) {
            $property = $this->publicProperty($propertyName);

            if (! $property) {
                continue;
            }

            $propertyType = $property->getType();

            if ($propertyType instanceof ReflectionNamedType && $propertyType->isBuiltin()) {
                $this->$propertyName = (new PropertyValue(value: $propertyValue))->value;

                continue;
            }

            if ($propertyType instanceof ReflectionNamedType) {
                $className = $propertyType->getName();
                $this->$propertyName = new $className($propertyValue ?? []);
            }
        }
    }

    public function defaultOverrides(): array
    {
        return [];
    }

    public function defaultOverride(string $key): mixed
    {
        return $this->defaultOverrides()[$key] ?? null;
    }

    public static function key(string $key): string
    {
        return static::SECTION_KEY."_{$key}";
    }

    public function defaultValue($key): mixed
    {
        return $this->defaultOverrides()[$key] ?? $this->publicProperty($key)?->getDefaultValue();
    }

    public function toFilamentData($class = null, $parentKey = null): array
    {
        $class = $class ?? $this;
        $parentKey = $parentKey ?? static::SECTION_KEY;

        $data = [];
        $properties = collect(get_object_vars($class))
            ->filter(fn ($value, $key) => ! str_starts_with($key, '_'));
        $defaultInstance = new $class([]);

        foreach ($properties as $key => $value) {
            if ($value instanceof TemplateComponent) {
                $nestedData = $this->toFilamentData($value, "{$parentKey}_{$key}");
                $data = array_merge($data, $nestedData);

                continue;
            }

            $isDefaultValue = ($value == $defaultInstance->$key || $defaultInstance->defaultOverride($key) == $value);

            $data["{$parentKey}_{$key}"] = $isDefaultValue ? null : $value;
        }

        return $data;
    }

    public static function fromArray($data, $model = null): self
    {
        $sectionData = self::valuesFromData(new static([]), $data);

        if (! Str::contains(request()->path(), 'admin') && ! Str::contains(request()->path(), 'livewire/update')) {
            $sectionData->afterFrom($model);
        }

        return $sectionData;
    }

    public static function valuesFromData($section, $data, $defaultValueOverrides = []): self
    {
        $values = [];
        foreach ($data as $key => $value) {
            $defaultValue = $defaultValueOverrides[$key] ?? $section->$key ?? null;
            $values[$key] = (new PropertyValue(value: $value, defaultValue: $defaultValue))->value;
        }

        return new static($values);
    }

    public function afterFrom($model): void
    {
    }

    public function clearDefaultValues(): self
    {
        foreach ($this->publicProperties() as $property) {
            if (gettype($property) === 'string') {
                $property = (new ReflectionClass($this))->getProperty($property);
            }

            $propertyName = $property->getName();

            if ($property->getDefaultValue() == $this->$propertyName) {
                $this->$propertyName = null;
            }
        }

        return $this;
    }
}
