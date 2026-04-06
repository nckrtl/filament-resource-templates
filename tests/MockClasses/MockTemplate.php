<?php

namespace NckRtl\FilamentResourceTemplates\Tests\MockClasses;

use NckRtl\FilamentResourceTemplates\TemplateOLD;

class MockTemplate extends TemplateOLD
{
    const NAME = 'default';

    const SECTIONS = [
        MockSection::SECTION_KEY => MockSection::class,
    ];
}
