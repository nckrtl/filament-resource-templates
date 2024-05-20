<?php

namespace NckRtl\FilamentResourceTemplates\Tests\MockClasses;

use Illuminate\Database\Eloquent\Model;

class MockModel extends Model
{
    protected $attributes = [
        'template' => 'default',
        'content' => [],
    ];

    protected $guarded = [];
}
