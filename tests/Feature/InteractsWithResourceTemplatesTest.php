<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NckRtl\FilamentResourceTemplates\Contracts\HasResourceTemplates;
use NckRtl\FilamentResourceTemplates\Exceptions\InvalidStoredTemplate;
use NckRtl\FilamentResourceTemplates\Template;
use NckRtl\FilamentResourceTemplates\Traits\InteractsWithResourceTemplates;
use Spatie\LaravelData\Data;

beforeEach(function (): void {
    Schema::create('templated_pages', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->string('template');
        $table->json('data')->nullable();
        $table->timestamps();
    });
});

it('hydrates registered templates from model and nested json values', function (): void {
    $page = TemplatedPage::query()->create([
        'title' => 'Database title',
        'template' => HomeTemplate::class,
        'data' => [
            'title' => 'Stale JSON title',
            'seo' => [
                'title' => 'SEO title',
                'description' => 'SEO description',
            ],
        ],
    ]);

    $template = $page->resourceTemplate();

    expect($template)
        ->toBeInstanceOf(HomeTemplate::class)
        ->and($template->title)->toBe('Database title')
        ->and($template->seo)->toBeInstanceOf(SeoData::class)
        ->and($template->seo->title)->toBe('SEO title')
        ->and($template->seo->description)->toBe('SEO description');
});

it('rejects template classes that are not registered for the model', function (): void {
    $page = TemplatedPage::query()->create([
        'title' => 'Database title',
        'template' => OtherTemplate::class,
        'data' => [],
    ]);

    expect(fn (): Template => $page->resourceTemplate())
        ->toThrow(InvalidStoredTemplate::class);
});

it('rejects blank stored template values', function (): void {
    $page = TemplatedPage::query()->create([
        'title' => 'Database title',
        'template' => '',
        'data' => [],
    ]);

    expect(fn (): Template => $page->resourceTemplate())
        ->toThrow(InvalidStoredTemplate::class);
});

final class SeoData extends Data
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}
}

final class HomeTemplate extends Template
{
    public function __construct(
        public string $title,
        public SeoData $seo,
    ) {}
}

final class OtherTemplate extends Template
{
    public function __construct(public string $title) {}
}

final class TemplatedPage extends Model implements HasResourceTemplates
{
    use InteractsWithResourceTemplates;

    protected $table = 'templated_pages';

    protected $guarded = [];

    /** @return array<int, class-string<Template>> */
    public static function resourceTemplates(): array
    {
        return [HomeTemplate::class];
    }

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
