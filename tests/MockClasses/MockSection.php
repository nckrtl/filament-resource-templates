<?php

namespace NckRtl\FilamentResourceTemplates\Tests\MockClasses;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use NckRtl\FilamentResourceTemplates\SectionTemplate;

class MockSection extends SectionTemplate
{
    const SECTION_KEY = 'mock';

    public ?string $text = 'value';

    public static function form(): Component
    {
        return Section::make('Inhoud')->schema(
            [
                RichEditor::make('content_text')
                    ->label('Titel'),
            ]
        );
    }
}
