<?php

declare(strict_types=1);

namespace Winter\LaravelBoost\Classes\Support;

final class WinterDocumentationSources
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'winter_cms' => [
                'name' => 'Winter CMS',
                'provider' => 'winter_cms',
                'base_url' => 'https://wintercms.com/docs',
                'version' => '1.2',
                'sitemap' => 'https://wintercms.com/docs/sitemap.xml',
                'sections' => [
                    'general' => 'https://wintercms.com/docs/v1.2/docs',
                    'markup' => 'https://wintercms.com/docs/v1.2/markup',
                    'ui' => 'https://wintercms.com/docs/v1.2/ui',
                    'api' => 'https://wintercms.com/docs/v1.2/api',
                ],
            ],
            'twig' => [
                'name' => 'Twig',
                'provider' => 'twig',
                'base_url' => 'https://twig.symfony.com/doc',
                'version' => '3.x',
                'sitemap' => 'https://twig.symfony.com/sitemap.xml',
                'sections' => [
                    'twig' => 'https://twig.symfony.com/doc/3.x/index.html',
                    'templates' => 'https://twig.symfony.com/doc/3.x/templates.html',
                    'syntax' => 'https://twig.symfony.com/doc/3.x/templates.html#twig-for-template-designers',
                    'filters' => 'https://twig.symfony.com/doc/3.x/filters/index.html',
                    'functions' => 'https://twig.symfony.com/doc/3.x/functions/index.html',
                    'tags' => 'https://twig.symfony.com/doc/3.x/tags/index.html',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function searchableSources(): array
    {
        $sources = self::all();

        return [
            ...$sources['winter_cms']['sections'],
            'twig' => $sources['twig']['sections']['twig'],
        ];
    }

    public static function winterSitemap(): string
    {
        return self::all()['winter_cms']['sitemap'];
    }

    public static function sourcePrefix(string $source): ?string
    {
        $searchableSources = self::searchableSources();

        return $searchableSources[$source] ?? null;
    }
}
