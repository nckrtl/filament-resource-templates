<?php

namespace NckRtl\FilamentResourceTemplates;

use Spatie\LaravelData\Data;

class TestDto extends Data
{
    public function __construct(
        public string $title,
        public string $content,
    ) {
    }
}
