<?php

declare(strict_types=1);

namespace NckRtl\FilamentResourceTemplates\Tests\Feature;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NckRtl\FilamentResourceTemplates\Contracts\HasResourceTemplates;
use NckRtl\FilamentResourceTemplates\SectionTemplate;
use NckRtl\FilamentResourceTemplates\Template;
use NckRtl\FilamentResourceTemplates\Traits\HasMutateFormData;
use NckRtl\FilamentResourceTemplates\Traits\HasTemplateForm;
use NckRtl\FilamentResourceTemplates\Traits\InteractsWithResourceTemplates;

beforeEach(function (): void {
    Schema::create('form_articles', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->timestamp('published_at')->nullable();
        $table->string('template');
        $table->json('data');
        $table->timestamps();
    });
});

it('stores model fields in columns and nested sections in json', function (): void {
    $result = Template::mutateFormDataBeforeCreateOrUpdate([
        'template' => ArticleTemplate::class,
        'temp_data' => [
            ArticleTemplate::key() => [
                'title' => 'Database title',
                'published_at' => '2026-07-17 12:00:00',
                'seo_title' => 'SEO title',
                'seo_description' => 'SEO description',
            ],
        ],
    ]);

    expect($result)
        ->toMatchArray([
            'template' => ArticleTemplate::class,
            'title' => 'Database title',
            'published_at' => '2026-07-17 12:00:00',
        ])
        ->and($result['data'])->toBe([
            'seo' => [
                'title' => 'SEO title',
                'description' => 'SEO description',
            ],
        ])
        ->and($result['data'])->not->toHaveKey('title')
        ->and($result['data'])->not->toHaveKey('published_at');
});

it('hydrates model and nested json values into transient form state', function (): void {
    $result = Template::mutateFormDataBeforeFill([
        'template' => ArticleTemplate::class,
        'title' => 'Database title',
        'published_at' => '2026-07-17 12:00:00',
        'data' => [
            'seo' => [
                'title' => 'SEO title',
                'description' => 'SEO description',
            ],
        ],
    ]);

    expect($result['template'])->toBe(ArticleTemplate::class)
        ->and($result['data'])->toBe([])
        ->and($result['temp_data'][ArticleTemplate::key()])->toMatchArray([
            'title' => 'Database title',
            'published_at' => '2026-07-17 12:00:00',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
        ]);
});

it('prefixes fields nested inside section components before they have a livewire container', function (): void {
    $components = Template::templateSchema(ArticleTemplate::class);
    $section = collect($components)
        ->first(fn (Component $component): bool => $component instanceof Section && $component->getHeading() === 'SEO');

    expect($section)->toBeInstanceOf(Section::class)
        ->and(collect($section->getDefaultChildComponents())
            ->filter(fn (mixed $component): bool => $component instanceof TextInput)
            ->map(fn (TextInput $component): string => $component->getName())
            ->values()
            ->all())
        ->toBe(['seo_title', 'seo_description']);
});

it('allows templates to embed their configured sections in a custom layout', function (): void {
    $components = Template::templateSchema(EmbeddedArticleTemplate::class);

    expect(collect($components)
        ->filter(fn (Component $component): bool => $component instanceof Section)
        ->map(fn (Section $component): string => (string) $component->getHeading())
        ->values()
        ->all())
        ->toBe(['Article']);
});

final class SeoSection extends SectionTemplate
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}

    public static function key(): string
    {
        return 'seo';
    }

    /** @return array<int, Component> */
    public static function schema(): array
    {
        return [
            Section::make('SEO')
                ->schema([
                    TextInput::make('title'),
                    TextInput::make('description'),
                ]),
        ];
    }
}

final class ArticleTemplate extends Template
{
    public static string $filamentResource = FormArticleResource::class;

    public function __construct(
        public string $title,
        public ?string $published_at,
        public SeoSection $seo,
    ) {}

    public static function key(): string
    {
        return 'article';
    }

    /** @return array<int, class-string<SectionTemplate>> */
    public static function sections(): array
    {
        return [SeoSection::class];
    }
}

final class EmbeddedArticleTemplate extends Template
{
    public static string $filamentResource = FormArticleResource::class;

    public function __construct(
        public string $title,
        public ?string $published_at,
        public SeoSection $seo,
    ) {}

    /** @return array<int, Component> */
    public static function schema(): array
    {
        return [
            Section::make('Article')
                ->schema([
                    TextInput::make('title'),
                    ...array_map(
                        fn (Component $component): Component => self::rebuildWithPrefixedKeys($component, SeoSection::key()),
                        SeoSection::schema(),
                    ),
                ]),
        ];
    }

    public static function hasEmbeddedSections(): bool
    {
        return true;
    }

    /** @return array<int, class-string<SectionTemplate>> */
    public static function sections(): array
    {
        return [SeoSection::class];
    }
}

final class FormArticle extends Model implements HasResourceTemplates
{
    use InteractsWithResourceTemplates;

    protected $table = 'form_articles';

    protected $guarded = [];

    /** @return array<int, class-string<Template>> */
    public static function resourceTemplates(): array
    {
        return [ArticleTemplate::class];
    }
}

final class FormArticleResource extends Resource
{
    use HasTemplateForm;

    protected static ?string $model = FormArticle::class;
}

final class CreateFormArticle extends CreateRecord
{
    use HasMutateFormData;

    protected static string $resource = FormArticleResource::class;
}

final class EditFormArticle extends EditRecord
{
    use HasMutateFormData;

    protected static string $resource = FormArticleResource::class;
}
