<?php

namespace NckRtl\FilamentResourceTemplates;

use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data;

class TemplateBase extends Data
{
    public function publicProperties($fullProperty = false): array
    {
        return array_map(
            fn ($property) => $fullProperty ? $property : $property->getName(),
            (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC)
        );
    }

    public function publicProperty(string $key): ?ReflectionProperty
    {
        foreach ($this->publicProperties() as $property) {
            if (gettype($property) === 'string') {
                $property = (new ReflectionClass($this))->getProperty($property);
            }

            if ($property->getName() === $key) {
                return $property;
            }
        }

        return null;
    }
}
