<?php

namespace NckRtl\FilamentResourceTemplates\Traits;

use Filament\Schemas\Schema;
use LogicException;
use NckRtl\FilamentResourceTemplates\Contracts\HasResourceTemplates;
use NckRtl\FilamentResourceTemplates\Template;

trait HasTemplateForm
{
    public static function form(Schema $schema): Schema
    {
        $model = static::getModel();

        if (! is_subclass_of($model, HasResourceTemplates::class)) {
            throw new LogicException("Model [{$model}] must implement [".HasResourceTemplates::class.']');
        }

        return Template::form($schema, $model::resourceTemplates());
    }
}
