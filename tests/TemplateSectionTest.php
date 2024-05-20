<?php

use NckRtl\FilamentResourceTemplates\TemplateSection;
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
    $mockClass = new class($properties) extends TemplateSection
    {
        public string $example_property;

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties(): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = new $mockClass($properties);
    expect($templateSection->example_property)->toBe('example_value');
});

test('defaultOverrides method returns correct default overrides', function () {
    $mockClass = new class([]) extends TemplateSection
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
    $mockClass = new class([]) extends TemplateSection
    {
        public function defaultOverrides(): array
        {
            return ['key' => 'default_value'];
        }
    };

    $defaultOverride = $mockClass->defaultOverride('key');
    expect($defaultOverride)->toBe('default_value');
});

test('key method returns correct key', function () {
    $key = TemplateSection::key('example');
    expect($key)->toBe('_example');
});

test('defaultValue method returns correct value', function () {
    $mockClass = new class([]) extends TemplateSection
    {
        public string $example_property = 'default_value';

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties(): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $defaultValue = $mockClass->defaultValue('example_property');
    expect($defaultValue)->toBe('default_value');
});

test('toFilamentData method returns correct data', function () {
    $mockClass = new class(['example_property' => 'value']) extends TemplateSection
    {
        const SECTION_KEY = 'example';

        public ?string $example_property = '';
    };

    $templateSection = new $mockClass(['example_property' => 'value']);
    $filamentData = $templateSection->toFilamentData();

    expect($filamentData)->toBe(['example_example_property' => 'value']);
});

test('fromArray method works correctly', function () {
    $data = ['example_property' => 'value'];
    $mockClass = new class($data) extends TemplateSection
    {
        public string $example_property;

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties(): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = $mockClass::fromArray($data);
    expect($templateSection->example_property)->toBe('value');
});

test('valuesFromData method works correctly', function () {
    $data = ['example_property' => 'value'];
    $mockClass = new class($data) extends TemplateSection
    {
        public string $example_property;

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties(): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = $mockClass::valuesFromData(new $mockClass([]), $data);
    expect($templateSection->example_property)->toBe('value');
});

test('clearDefaultValues method works correctly', function () {
    $mockClass = new class(['example_property' => 'value']) extends TemplateSection
    {
        public ?string $example_property = 'default_value';

        public function publicProperty(string $key): ?ReflectionProperty
        {
            return new ReflectionProperty($this, $key);
        }

        public function publicProperties(): array
        {
            return [(new ReflectionProperty($this, 'example_property'))];
        }
    };

    $templateSection = new $mockClass(['example_property' => 'default_value']);
    $templateSection->clearDefaultValues();

    expect($templateSection->example_property)->toBeNull();
});
