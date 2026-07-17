<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates\Traits;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use NckRtl\FilamentResourceTemplates\Contracts\HasResourceTemplates;
use NckRtl\FilamentResourceTemplates\Template;

trait HasMutateFormData
{
    abstract public function getRecord(): ?Model;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Template::mutateFormDataBeforeCreateOrUpdate(
            $this->withSelectedTemplate($data),
        );
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Template::mutateFormDataBeforeFill($data);
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Template::mutateFormDataBeforeCreateOrUpdate(
            $this->withSelectedTemplate($data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withSelectedTemplate(array $data): array
    {
        $resource = static::getResource();
        $modelClass = $resource::getModel();

        if (! is_subclass_of($modelClass, HasResourceTemplates::class)) {
            throw new LogicException("Model [{$modelClass}] must implement [".HasResourceTemplates::class.']');
        }

        $record = $this->getRecord();
        $storedTemplate = $record instanceof Model ? $record->getAttribute('template') : null;
        $templates = $modelClass::resourceTemplates();
        $template = $storedTemplate ?? ($data['template'] ?? null);

        if ($template === null && count($templates) === 1) {
            $template = $templates[0];
        }

        if (! is_string($template) || ! in_array($template, $templates, true)) {
            throw new LogicException('A registered resource template is required.');
        }

        $data['template'] = $template;

        return $data;
    }
}
