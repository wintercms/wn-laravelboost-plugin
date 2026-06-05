<?php

declare(strict_types=1);

namespace Winter\LaravelBoost\Classes\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;
use Winter\LaravelBoost\Classes\Support\WinterDocumentationSearchParser;
use Winter\LaravelBoost\Classes\Support\WinterDocumentationSources;

#[IsReadOnly]
class SearchWinterDocs extends Tool
{
    use MakesHttpRequests;

    /**
     * @var array<int, string>|null
     */
    protected ?array $winterSitemapUrls = null;

    protected string $description = 'Search Winter CMS documentation through MCP. Uses Winter CMS documentation search for Winter docs and a sitemap-backed fallback for Twig pages.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'queries' => $schema->array()
                ->items($schema->string()->description('Search query'))
                ->description('List of Winter CMS documentation queries to search for.')
                ->required(),
            'sources' => $schema->array()
                ->items($schema->string()->description('Documentation source to search. Available values: general, markup, ui, api, twig'))
                ->description('Optional Winter documentation sources to search. Defaults to all Winter CMS sources and Twig.'),
            'token_limit' => $schema->integer()
                ->description('Approximate maximum number of tokens to return. Defaults to 3,000.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $rawQueries = $this->resolveArrayParam($request->get('queries'));

        if ($rawQueries instanceof Response) {
            return $rawQueries;
        }

        $queries = array_values(array_filter(array_map(static fn (string $query): string => trim($query), $rawQueries), static fn (string $query): bool => $query !== '' && $query !== '*'));

        if ($queries === []) {
            return Response::error('At least one non-empty query is required.');
        }

        $requestedSources = $this->resolveRequestedSources($request->get('sources'));

        if ($requestedSources instanceof Response) {
            return $requestedSources;
        }

        $results = [];
        $errors = [];

        foreach ($requestedSources as $source => $url) {
            if ($source === 'twig') {
                try {
                    $results = [...$results, ...$this->searchTwig($queries)];
                } catch (Throwable $throwable) {
                    $errors[] = 'Twig docs search failed: '.$throwable->getMessage();
                }

                continue;
            }

            try {
                $results = [...$results, ...$this->searchWinterSource($source, $url, $queries)];
            } catch (Throwable $throwable) {
                $errors[] = sprintf('Winter docs search failed for source [%s]: %s', $source, $throwable->getMessage());
            }
        }

        $ranked = WinterDocumentationSearchParser::rankResults($results, $queries);
        $characterLimit = max(4000, min(((int) ($request->get('token_limit') ?? 3000)) * 4, 120000));
        $markdown = WinterDocumentationSearchParser::formatMarkdown($ranked, $queries, array_keys($requestedSources), $characterLimit);

        if ($errors !== []) {
            $markdown .= "\n\n## Warnings\n\n";

            foreach ($errors as $error) {
                $markdown .= '- '.$error."\n";
            }
        }

        return Response::text($markdown);
    }

    /**
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    protected function searchWinterSection(string $source, string $url, string $query): array
    {
        $pageResponse = $this->get($url);

        if (! $pageResponse->successful()) {
            throw new \RuntimeException('Failed to fetch search page: '.$pageResponse->body());
        }

        $pageHtml = $pageResponse->body();
        $sessionKey = WinterDocumentationSearchParser::extractHiddenInputValue($pageHtml, '_session_key');
        $token = WinterDocumentationSearchParser::extractHiddenInputValue($pageHtml, '_token');

        if ($sessionKey === null || $token === null) {
            throw new \RuntimeException('Failed to resolve Winter documentation search tokens.');
        }

        $searchResponse = $this->client()
            ->asForm()
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-WINTER-REQUEST-HANDLER' => 'search::onSearch',
                'X-OCTOBER-REQUEST-HANDLER' => 'search::onSearch',
            ])
            ->post($url, [
                '_session_key' => $sessionKey,
                '_token' => $token,
                'query' => $query,
            ]);

        if (! $searchResponse->successful()) {
            throw new \RuntimeException('Failed to execute Winter documentation search: '.$searchResponse->body());
        }

        return WinterDocumentationSearchParser::parseWinterSearchResponse($searchResponse->body(), $source);
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    protected function searchWinterSource(string $source, string $url, array $queries): array
    {
        $results = [];

        if ($source !== 'api') {
            foreach (WinterDocumentationSearchParser::expandQueries($queries) as $queryVariant) {
                $variantResults = $this->searchWinterSection($source, $url, $queryVariant);

                if ($variantResults === []) {
                    continue;
                }

                $results = [...$results, ...$variantResults];

                if (count($results) >= 8) {
                    break;
                }
            }
        }

        if ($results !== []) {
            return $results;
        }

        return $this->searchWinterSitemap($source, $queries);
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    protected function searchTwig(array $queries): array
    {
        $twigConfig = WinterDocumentationSources::all()['twig'];
        $sitemapResponse = $this->get($twigConfig['sitemap']);

        if (! $sitemapResponse->successful()) {
            throw new \RuntimeException('Failed to fetch Twig sitemap: '.$sitemapResponse->body());
        }

        $urls = WinterDocumentationSearchParser::parseSitemap($sitemapResponse->body(), 'https://twig.symfony.com/doc/3.x/');

        return WinterDocumentationSearchParser::buildTwigResults($urls, $queries);
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    protected function searchWinterSitemap(string $source, array $queries): array
    {
        $prefix = WinterDocumentationSources::sourcePrefix($source);

        if ($prefix === null) {
            return [];
        }

        $urls = array_values(array_filter(
            $this->getWinterSitemapUrls(),
            static fn (string $url): bool => str_starts_with($url, $prefix)
        ));

        return WinterDocumentationSearchParser::buildWinterSitemapResults($urls, $source, $queries);
    }

    /**
     * @return array<int, string>
     */
    protected function getWinterSitemapUrls(): array
    {
        if ($this->winterSitemapUrls !== null) {
            return $this->winterSitemapUrls;
        }

        $sitemapResponse = $this->get(WinterDocumentationSources::winterSitemap());

        if (! $sitemapResponse->successful()) {
            throw new \RuntimeException('Failed to fetch Winter sitemap: '.$sitemapResponse->body());
        }

        $this->winterSitemapUrls = WinterDocumentationSearchParser::parseSitemap($sitemapResponse->body());

        return $this->winterSitemapUrls;
    }

    /**
     * @return array<string, string>|Response
     */
    protected function resolveRequestedSources(mixed $value): array|Response
    {
        $availableSources = WinterDocumentationSources::searchableSources();

        if ($value === null) {
            return $availableSources;
        }

        $resolved = $this->resolveArrayParam($value);

        if ($resolved instanceof Response) {
            return $resolved;
        }

        $normalized = array_values(array_unique(array_map(static fn (string $source): string => trim($source), $resolved)));
        $invalidSources = array_values(array_diff($normalized, array_keys($availableSources)));

        if ($invalidSources !== []) {
            return Response::error('Invalid sources requested: '.implode(', ', $invalidSources));
        }

        return array_intersect_key($availableSources, array_flip($normalized));
    }

    /**
     * @return array<int, mixed>|null|Response
     */
    protected function resolveArrayParam(mixed $value): array|null|Response
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return Response::error('Invalid parameter: expected an array or JSON array string.');
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return Response::error('Invalid parameter: '.json_last_error_msg());
        }

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            return Response::error('Invalid parameter: expected a JSON array.');
        }

        return $decoded;
    }
}

