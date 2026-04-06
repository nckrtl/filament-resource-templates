<?php

namespace NckRtl\FilamentResourceTemplates\Traits;

use NckRtl\FilamentResourceTemplates\Template;

trait HasMutateFormData
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Template::mutateFormDataBeforeCreateOrUpdate($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Template::mutateFormDataBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Template::mutateFormDataBeforeCreateOrUpdate($data);
    }

    protected function afterSave(): void
    {
        // Refresh the record from the database
        $this->record->refresh();

        // Refill the form with the updated data
        $this->fillForm();
    }
}
