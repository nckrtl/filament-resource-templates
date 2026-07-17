<?php

namespace NckRtl\FilamentResourceTemplates\Facades;

use Illuminate\Support\Facades\Facade;
use NckRtl\FilamentResourceTemplates\Template;

/**
 * @see \NckRtl\FilamentResourceTemplates\FilamentResourceTemplates
 */
class FilamentResourceTemplates extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Template::class;
    }
}
