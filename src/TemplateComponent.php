<?php

namespace NckRtl\FilamentResourceTemplates;

use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data;

class TemplateComponent extends Data
{
    public function __construct(array $properties)
    {
        if (! empty($properties)) {
            foreach ($this->publicProperties() as $property) {
                $this->$property = $properties[$property] ?? null;
            }
        }
    }

    private function publicProperties(): array
    {
        return array_map(
            fn ($property) => $property->getName(),
            (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC)
        );
    }

    public function publicProperty(string $key): mixed
    {
        $properties = array_filter($this->publicProperties(), fn ($property) => $property->getName() == $key);

        return reset($properties);
    }

    public function defaultOverrides(): array
    {
        return [];
    }

    public function defaultOverride(string $key): mixed
    {
        return $this->defaultOverrides()[$key] ?? null;
    }

    public static function key($parentKey, $key): string
    {
        return "{$parentKey}_{$key}";
    }
}
