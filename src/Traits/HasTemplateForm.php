<?php

namespace NckRtl\FilamentResourceTemplates\Traits;

use Filament\Schemas\Schema;
use NckRtl\FilamentResourceTemplates\Template;

trait HasTemplateForm
{
    public static function form(Schema $schema): Schema
    {
        return Template::form($schema, static::templates());
    }
}
