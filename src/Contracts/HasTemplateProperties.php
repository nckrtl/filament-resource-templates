<?php

namespace NckRtl\FilamentResourceTemplates\Contracts;

interface HasTemplateProperties
{
    public static function key(): string;

    public static function label(): string;

    public static function schema(): array;
}
