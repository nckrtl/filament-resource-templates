<?php

namespace NckRtl\FilamentResourceTemplates;

use Filament\Resources\Pages\CreateRecord;

class TemplateCreateRecord extends CreateRecord
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Template::mutateFormDataBeforeCreateOrUpdate($data);
    }
}
