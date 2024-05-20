<?php

use NckRtl\FilamentResourceTemplates\Template;
use NckRtl\FilamentResourceTemplates\Tests\MockClasses\MockModel;
use NckRtl\FilamentResourceTemplates\Tests\MockClasses\MockSection;
use NckRtl\FilamentResourceTemplates\Tests\MockClasses\MockTemplate;

it('constructor initializes properties correctly', function () {
    $properties = ['template' => 'default', 'content' => ['mock' => ['text' => 'value']]];
    $template = new MockTemplate($properties);

    expect($template->template)->toBe('default');
    expect($template->content)->toBe(['mock' => ['text' => 'value']]);
});

it('sections method returns correct sections', function () {
    $sections = MockTemplate::sections();

    expect($sections)->toBe([MockSection::SECTION_KEY => MockSection::class]);
});

it('convertContent method works correctly', function () {
    $template = new MockTemplate(['template' => 'default', 'content' => ['mock' => ['text' => 'value']]]);
    $template->convertContent();

    expect($template->content['mock'])->toBeInstanceOf(MockSection::class);
});

it('fromArray method converts model to template instance', function () {
    $model = new MockModel(['template' => 'default', 'content' => ['mock' => ['text' => 'value']]]);
    $template = MockTemplate::fromArray($model);

    expect($template)->toBeInstanceOf(Template::class);
    expect($template->template)->toBe('default');
});

it('fromModel method converts model to template instance', function () {
    $model = new MockModel(['template' => 'default', 'content' => ['mock' => ['text' => 'value']]]);
    $template = MockTemplate::fromModel($model);

    expect($template)->toBeInstanceOf(MockTemplate::class);
    expect($template->template)->toBe('default');
});

it('toFilamentData method returns correct data', function () {
    $data = ['template' => 'default', 'content' => ['mock' => ['text' => 'value']]];

    $template = MockTemplate::fromArray($data);

    $filamentData = Template::toFilamentData($data);

    expect($filamentData)->toBeArray();
    expect($filamentData)->toHaveKey('template', 'default');
});

it('fromFilamentData method works correctly', function () {
    $data = ['template' => 'default', 'content' => ['mock' => ['text' => 'value']]];

    $template = MockTemplate::fromFilamentData($data);

    expect($template)->toBeInstanceOf(Template::class);
    expect($template->content['mock'])->toBeInstanceOf(MockSection::class);
});

it('groupFilamentData method works correctly', function () {
    $data = ['mock_key' => 'value'];
    $groupedData = Template::groupFilamentData($data);

    expect($groupedData['mock']['key'])->toBe('value');
});

it('sift method works correctly', function () {
    $data = ['key1' => '', 'key2' => ['subkey1' => '', 'subkey2' => 'value']];
    $filteredData = Template::sift($data);

    expect($filteredData)->toBe(['key2' => ['subkey2' => 'value']]);
});

it('mutateFormDataBeforeFill method works correctly', function () {
    $data = ['template' => 'NckRtl\FilamentResourceTemplates\Tests\MockClasses\MockTemplate', 'content' => ['mock' => ['text' => 'value']]];

    $template = MockTemplate::fromFilamentData($data);

    $mutatedData = $template::mutateFormDataBeforeFill($data);

    expect($mutatedData['content'])->toBe([]);
});

it('mutateFormDataBeforeCreateOrUpdate method works correctly', function () {
    $data = [
        'template' => MockTemplate::class,
        'temp_content' => [
            'mock_template' => ['mock' => ['text' => 'value not equal to default value']],
        ],
    ];

    $mutatedData = Template::mutateFormDataBeforeCreateOrUpdate($data);

    expect($mutatedData['content'])->toBe(['mock' => ['text' => 'value not equal to default value']]);
});
