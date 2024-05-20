<?php

namespace NckRtl\FilamentResourceTemplates\Tests\MockClasses;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use NckRtl\FilamentResourceTemplates\TemplateSection;

class MockSection extends TemplateSection
{
    const SECTION_KEY = 'mock';

    public ?string $text = 'value';

    public static function form()
    {
        return Section::make('Inhoud')->schema(
            [
                RichEditor::make(static::key('text'))
                    ->label('Titel'),
            ]
        );
    }
}
