<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates;

use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;
use NckRtl\FilamentResourceTemplates\Contracts\HasTemplateProperties;
use Spatie\LaravelData\Data;

abstract class SectionTemplate extends Data implements HasTemplateProperties
{
    public static function key(): string
    {
        return Str::snake(class_basename(static::class));
    }

    public static function label(): string
    {
        return Str::headline(static::key());
    }

    /** @return array<int, Component> */
    public static function schema(): array
    {
        return [];
    }
}
