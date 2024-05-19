<?php

namespace NckRtl\FilamentResourceTemplates;

class TemplateComponent extends TemplateBase
{
    public function __construct(array $properties)
    {
        if (! empty($properties)) {
            foreach ($this->publicProperties() as $property) {
                $this->$property = $properties[$property] ?? null;
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

    public static function key($parentKey, $key): string
    {
        return "{$parentKey}_{$key}";
    }
}
