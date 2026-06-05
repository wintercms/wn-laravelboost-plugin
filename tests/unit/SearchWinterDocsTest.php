<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use PHPUnit\Framework\TestCase;
use Winter\LaravelBoost\Classes\Tools\SearchWinterDocs;

require_once __DIR__.'/../../classes/Support/WinterDocumentationSources.php';
require_once __DIR__.'/../../classes/Support/WinterDocumentationSearchParser.php';
require_once __DIR__.'/../../classes/Tools/SearchWinterDocs.php';

final class SearchWinterDocsTest extends TestCase
{
    public function testItFallsBackToSitemapResultsForVerboseWinterQueries(): void
    {
        $tool = new class extends SearchWinterDocs
        {
            /** @var array<int, string> */
            public array $variants = [];

            protected function searchWinterSection(string $source, string $url, string $query): array
            {
                $this->variants[] = $query;

                return [];
            }

            protected function getWinterSitemapUrls(): array
            {
                return [
                    'https://wintercms.com/docs/v1.2/docs/plugin/components',
                    'https://wintercms.com/docs/v1.2/docs/console/scaffolding',
                    'https://wintercms.com/docs/v1.2/docs/setup/installation',
                ];
            }

            protected function searchTwig(array $queries): array
            {
                return [];
            }
        };

        $response = $tool->handle(new Request([
            'queries' => [
                'components creating component simple component plugin component',
                'CMS component tutorial component class defineProperties onRun default.htm',
                'Winter CMS component class Plugin registerComponents',
            ],
            'sources' => ['general'],
        ]));

        $content = (string) $response->content();

        $this->assertStringContainsString('Plugin Components', $content);
        $this->assertStringContainsString('https://wintercms.com/docs/v1.2/docs/plugin/components', $content);
        $this->assertContains('component', $tool->variants);
        $this->assertContains('register component', $tool->variants);
    }

    public function testItUsesSitemapFallbackForApiWithoutCallingBrokenRemoteSearch(): void
    {
        $tool = new class extends SearchWinterDocs
        {
            public int $winterSearchCalls = 0;

            protected function searchWinterSection(string $source, string $url, string $query): array
            {
                $this->winterSearchCalls++;

                return [];
            }

            protected function getWinterSitemapUrls(): array
            {
                return [
                    'https://wintercms.com/docs/v1.2/api/Cms/Classes/ComponentBase',
                    'https://wintercms.com/docs/v1.2/api/Cms/Classes/Controller',
                ];
            }

            protected function searchTwig(array $queries): array
            {
                return [];
            }
        };

        $response = $tool->handle(new Request([
            'queries' => ['Winter CMS component class Plugin registerComponents'],
            'sources' => ['api'],
        ]));

        $content = (string) $response->content();

        $this->assertSame(0, $tool->winterSearchCalls);
        $this->assertStringContainsString('ComponentBase', $content);
        $this->assertStringNotContainsString('## Warnings', $content);
    }
}

