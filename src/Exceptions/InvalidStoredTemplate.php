<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class InvalidStoredTemplate extends RuntimeException
{
    public static function forModel(Model $model, mixed $template): self
    {
        $modelClass = $model::class;
        $templateName = is_string($template) && $template !== ''
            ? $template
            : '(blank)';

        return new self(
            "Template [{$templateName}] is not registered for [{$modelClass}] record [{$model->getKey()}].",
        );
    }
}
