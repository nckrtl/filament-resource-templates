<?php

namespace NckRtl\FilamentResourceTemplates;

use Filament\Forms\Form;
use Filament\Resources\Resource;

class TemplateResource extends Resource
{
    public static function form(Form $form): Form
    {
        return Template::templateForm($form, static::getTemplateClasses());
    }
}
