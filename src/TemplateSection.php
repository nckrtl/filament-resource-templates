<?php

namespace NckRtl\FilamentResourceTemplates;

use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data;

class TemplateSection extends Data
{
    const SECTION_KEY = '';

    public function __construct(array $properties)
    {
        foreach ($properties as $propertyName => $propertyValue) {
            $property = $this->publicProperty($propertyName);

            if (! $property) {
                continue;
            }

            $propertyType = $property->getType();

            if ($propertyType->isBuiltin()) {
                $this->$propertyName = (new PropertyValue(value: $propertyValue))->value;

                continue;
            }

            $className = $propertyType->getName();
            $this->$propertyName = new $className($propertyValue ?? []);
        }
    }

    public function publicProperties(): array
    {
        return (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    public function publicProperty(string $key): ?ReflectionProperty
    {
        foreach ($this->publicProperties() as $property) {
            if ($property->getName() === $key) {
                return $property;
            }
        }

        return null;
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

    public static function fromArray($data): self
    {
        return self::valuesFromData(new static([]), $data);
    }

    public static function valuesFromData($section, $data, $defaultValueOverrides = []): self
    {
        $values = [];
        foreach ($data as $key => $value) {
            $defaultValue = $defaultValueOverrides[$key] ?? $section->$key ?? null;
            $values[$key] = (new PropertyValue(defaultValue: $defaultValue, value: $value))->value;
        }

        return new static($values);
    }

    public function afterFrom($page): void
    {
        // Implement this method as needed
    }

    public function clearDefaultValues(): self
    {
        foreach ($this->publicProperties() as $property) {
            $propertyName = $property->getName();
            if ($property->getDefaultValue() == $this->$propertyName) {
                $this->$propertyName = null;
            }
        }

        return $this;
    }
}
