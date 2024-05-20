<?php

namespace NckRtl\FilamentResourceTemplates\Tests\MockClasses;

use NckRtl\FilamentResourceTemplates\Template;

class MockTemplate extends Template
{
    const NAME = 'default';

    const SECTIONS = [
        MockSection::SECTION_KEY => MockSection::class,
    ];
}
