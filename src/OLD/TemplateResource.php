<?php

namespace NckRtl\FilamentResourceTemplates\OLD;

use Illuminate\Support\Collection;

interface HasTemplates
{
    public static function getTemplates(): Collection;
}
