<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates\Traits;

use Illuminate\Support\Arr;
use NckRtl\FilamentResourceTemplates\Exceptions\InvalidStoredTemplate;
use NckRtl\FilamentResourceTemplates\Template;

trait InteractsWithResourceTemplates
{
    public function resourceTemplate(): Template
    {
        $template = $this->getAttribute('template');

        if (
            ! is_string($template)
            || ! is_subclass_of($template, Template::class)
            || ! in_array($template, static::resourceTemplates(), true)
        ) {
            throw InvalidStoredTemplate::forModel($this, $template);
        }

        $data = $this->getAttribute('data');
        $templateData = is_array($data) ? $data : [];
        $modelData = Arr::except($this->attributesToArray(), ['data', 'template']);

        return $template::from([
            ...$templateData,
            ...$modelData,
        ]);
    }
}
