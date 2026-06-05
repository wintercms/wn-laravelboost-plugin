<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Winter\LaravelBoost\Classes\Support\WinterDocumentationSearchParser;

require_once __DIR__.'/../../classes/Support/WinterDocumentationSearchParser.php';

final class WinterDocumentationSearchParserTest extends TestCase
{
    public function testItParsesWinterSearchResponses(): void
    {
        $payload = json_encode([
            '#search-results' => <<<'HTML'
                <div>
                    <a href="https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration">
                        <span class="inline-block">Component class definition</span>
                        <span class="block text-grey-800 font-semibold">Component registration</span>
                        <small class="hidden sm:block">Learn how to register plugin components in Winter CMS.</small>
                    </a>
                </div>
            HTML,
        ]);

        $results = WinterDocumentationSearchParser::parseWinterSearchResponse((string) $payload, 'general');

        $this->assertCount(1, $results);
        $this->assertSame('winter_cms', $results[0]['provider']);
        $this->assertSame('general', $results[0]['source']);
        $this->assertSame('Component registration', $results[0]['title']);
        $this->assertSame('https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration', $results[0]['url']);
        $this->assertStringContainsString('register plugin components', $results[0]['excerpt']);
    }

    public function testItRanksAndDeduplicatesResults(): void
    {
        $results = [
            [
                'provider' => 'winter_cms',
                'source' => 'general',
                'title' => 'Component registration',
                'url' => 'https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration',
                'excerpt' => 'Register plugin components in Winter CMS.',
            ],
            [
                'provider' => 'winter_cms',
                'source' => 'general',
                'title' => 'Component registration (duplicate)',
                'url' => 'https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration',
                'excerpt' => 'A weaker duplicate result.',
                'score' => 1,
            ],
            [
                'provider' => 'twig',
                'source' => 'twig',
                'title' => 'Filters',
                'url' => 'https://twig.symfony.com/doc/3.x/filters/index.html',
                'excerpt' => 'Twig filters reference.',
            ],
        ];

        $ranked = WinterDocumentationSearchParser::rankResults($results, ['component registration'], 5);

        $this->assertCount(1, array_filter($ranked, static fn (array $result): bool => $result['url'] === 'https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration'));
        $this->assertSame('Component registration', $ranked[0]['title']);
        $this->assertGreaterThan(0, $ranked[0]['score']);
    }

    public function testItBuildsTwigResultsFromASitemap(): void
    {
        $xml = <<<'XML'
            <urlset>
                <url><loc>https://twig.symfony.com/doc/3.x/filters/index.html</loc></url>
                <url><loc>https://twig.symfony.com/doc/3.x/functions/date.html</loc></url>
                <url><loc>https://twig.symfony.com/blog</loc></url>
            </urlset>
        XML;

        $urls = WinterDocumentationSearchParser::parseSitemap($xml, 'https://twig.symfony.com/doc/3.x/');
        $results = WinterDocumentationSearchParser::buildTwigResults($urls, ['filter']);

        $this->assertCount(1, $results);
        $this->assertSame('twig', $results[0]['provider']);
        $this->assertSame('Filters', $results[0]['title']);
    }

    public function testItFormatsMarkdownResults(): void
    {
        $markdown = WinterDocumentationSearchParser::formatMarkdown([
            [
                'provider' => 'winter_cms',
                'source' => 'general',
                'title' => 'Component registration',
                'url' => 'https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration',
                'excerpt' => 'Learn how to register plugin components in Winter CMS.',
                'score' => 200,
            ],
        ], ['component registration'], ['general'], 5000);

        $this->assertStringContainsString('# Winter documentation search results', $markdown);
        $this->assertStringContainsString('Component registration', $markdown);
        $this->assertStringContainsString('https://wintercms.com/docs/v1.2/docs/plugin/components#component-registration', $markdown);
    }

    public function testItExpandsNaturalLanguageQueriesIntoUsefulVariants(): void
    {
        $variants = WinterDocumentationSearchParser::expandQueries([
            'CMS component tutorial component class defineProperties onRun default.htm',
            'Winter CMS component class Plugin registerComponents',
        ], 20);

        $this->assertContains('define property', $variants);
        $this->assertContains('on run', $variants);
        $this->assertContains('register component', $variants);
        $this->assertContains('component', $variants);
    }

    public function testItBuildsWinterSitemapResultsForVerboseComponentQueries(): void
    {
        $results = WinterDocumentationSearchParser::buildWinterSitemapResults([
            'https://wintercms.com/docs/v1.2/docs/plugin/components',
            'https://wintercms.com/docs/v1.2/docs/console/scaffolding',
            'https://wintercms.com/docs/v1.2/docs/setup/installation',
        ], 'general', [
            'components creating component simple component plugin component',
            'Winter CMS component class Plugin registerComponents',
        ]);

        $ranked = WinterDocumentationSearchParser::rankResults($results, [
            'components creating component simple component plugin component',
            'Winter CMS component class Plugin registerComponents',
        ], 5);

        $this->assertNotEmpty($ranked);
        $this->assertSame('https://wintercms.com/docs/v1.2/docs/plugin/components', $ranked[0]['url']);
        $this->assertSame('Plugin Components', $ranked[0]['title']);
    }
}
