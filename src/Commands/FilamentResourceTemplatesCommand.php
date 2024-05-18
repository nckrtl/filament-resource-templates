<?php

namespace NckRtl\FilamentResourceTemplates\Commands;

use Illuminate\Console\Command;

class FilamentResourceTemplatesCommand extends Command
{
    public $signature = 'filament-resource-templates';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
