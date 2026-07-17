<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates\Contracts;

use NckRtl\FilamentResourceTemplates\Template;

interface HasResourceTemplates
{
    /** @return array<int, class-string<Template>> */
    public static function resourceTemplates(): array;

    public function resourceTemplate(): Template;
}
