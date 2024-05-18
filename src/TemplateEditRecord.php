<?php

namespace NckRtl\FilamentResourceTemplates;

use Filament\Resources\Pages\EditRecord;

class TemplateEditRecord extends EditRecord
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Template::mutateFormDataBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Template::mutateFormDataBeforeCreateOrUpdate($data);
    }
}
