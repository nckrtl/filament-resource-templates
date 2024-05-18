<?php

namespace NckRtl\FilamentResourceTemplates;

use Illuminate\Support\Str;

class PropertyValue
{
    public mixed $value = null;

    public function __construct(mixed $value, mixed $defaultValue = null)
    {
        if (Str::contains(request()->path(), 'admin') || Str::contains(request()->path(), 'livewire/update')) {
            $this->value = $value ?? null;

            return;
        }

        $this->value = $value ?? $defaultValue;
    }
}
