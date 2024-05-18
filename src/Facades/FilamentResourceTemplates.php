<?php

namespace NckRtl\FilamentResourceTemplates\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NckRtl\FilamentResourceTemplates\FilamentResourceTemplates
 */
class FilamentResourceTemplates extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NckRtl\FilamentResourceTemplates\Template::class;
    }
}
