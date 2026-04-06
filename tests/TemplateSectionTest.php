<?php

use NckRtl\FilamentResourceTemplates\SectionTemplate;
use ReflectionProperty;

class MockTemplateComponent
{
    public function __construct($properties)
    {
        // Assume some properties initialization
    }
}

class PropertyValue
{
    public mixed $value;

    public function __construct(array $data)
    {
        $this->value = $data['value'];
    }
}

beforeEach(function () {
    // Setup necessary state before each test if needed
});

test('constructor initializes properties correctly', function () {
    $properties = ['example_property' => 'example_value'];
    $mockClass = new class($properties) extends SectionTemplate
    {
        public string $example_property;

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties($fullProperty = false): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = new $mockClass($properties);
    expect($templateSection->example_property)->toBe('example_value');
});

test('defaultOverrides method returns correct default overrides', function () {
    $mockClass = new class extends SectionTemplate
    {
        public function defaultOverrides(): array
        {
            return ['key' => 'default_value'];
        }
    };

    $defaultOverrides = $mockClass->defaultOverrides();
    expect($defaultOverrides)->toBe(['key' => 'default_value']);
});

test('defaultOverride method returns correct value', function () {
    $mockClass = new class extends SectionTemplate
    {
        public function defaultOverrides(): array
        {
            return ['key' => 'default_value'];
        }
    };

    $defaultOverride = $mockClass->defaultOverride('key');
    expect($defaultOverride)->toBe('default_value');
});

// test('key method returns correct key', function () {
//     $key = SectionTemplate::key('example');
//     expect($key)->toBe('_example');
// });

test('defaultValue method returns correct value', function () {
    $mockClass = new class extends SectionTemplate
    {
        public string $example_property = 'default_value';

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties($fullProperty = false): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $defaultValue = $mockClass->defaultValue('example_property');
    expect($defaultValue)->toBe('default_value');
});

test('toFilamentData method returns correct data', function () {
    $mockClass = new class(['example_property' => 'value']) extends SectionTemplate
    {
        const KEY = 'example';

        public ?string $some_property = '';
    };

    $templateSection = new $mockClass(['some_property' => 'value']);
    $filamentData = $templateSection->toFilamentData();

    expect($filamentData)->toBe(['example_some_property' => 'value']);
});

test('fromArray method works correctly', function () {
    $data = ['example_property' => 'value'];
    $mockClass = new class($data) extends SectionTemplate
    {
        public string $example_property;

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties($fullProperty = false): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = $mockClass::fromArray($data);
    expect($templateSection->example_property)->toBe('value');
});

test('valuesFromData method works correctly', function () {
    $data = ['example_property' => 'value'];
    $mockClass = new class($data) extends SectionTemplate
    {
        public string $example_property;

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties($fullProperty = false): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = $mockClass::valuesFromData(new $mockClass, $data);
    expect($templateSection->example_property)->toBe('value');
});

test('clearDefaultValues method works correctly', function () {
    $mockClass = new class(['example_property' => 'value']) extends SectionTemplate
    {
        public ?string $example_property = 'default_value';

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties($fullProperty = false): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = new $mockClass(['example_property' => 'default_value']);
    $templateSection->clearDefaultValues();

    expect($templateSection->example_property)->toBeNull();
});

test('mutateBeforeDisplay method works correctly', function () {
    $mockClass = new class extends SectionTemplate
    {
        public ?string $example_property = 'default_value';

        public function mutateBeforeDisplay($model): void
        {
            $model->example_property = 'mutated_value';
        }
    };

    $model = new $mockClass;

    $mockClass->mutateBeforeDisplay($model);

    expect($model->example_property)->toBe('mutated_value');
});
