<?php

declare(strict_types=1);

namespace Winter\LaravelBoost\Classes\Support;

final class WinterDocumentationSearchParser
{
    /**
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    public static function parseWinterSearchResponse(string $payload, string $source): array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return [];
        }

        $html = $decoded['#search-results'] ?? '';

        if (! is_string($html) || trim($html) === '') {
            return [];
        }

        return self::parseWinterSearchHtml($html, $source);
    }

    /**
     * @return array<int, string>
     */
    public static function parseSitemap(string $xml, ?string $prefix = null): array
    {
        preg_match_all('/<loc>(.*?)<\/loc>/i', $xml, $matches);

        $urls = array_values(array_unique(array_map(
            static fn (string $url): string => html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5),
            $matches[1] ?? []
        )));

        if ($prefix === null) {
            return $urls;
        }

        return array_values(array_filter($urls, static fn (string $url): bool => str_starts_with($url, $prefix)));
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    public static function buildTwigResults(array $urls, array $queries): array
    {
        $results = [];

        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?? '';

            if (! str_starts_with($path, '/doc/3.x/')) {
                continue;
            }

            $title = self::humanizeTwigTitle($path);
            $excerpt = 'Twig documentation page: '.$path;
            $score = self::scoreResult($title, $excerpt, $url, $queries);

            if ($score <= 0) {
                continue;
            }

            $results[] = [
                'provider' => 'twig',
                'source' => 'twig',
                'title' => $title,
                'url' => $url,
                'excerpt' => $excerpt,
                'score' => $score,
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $urls
     * @param  array<int, string>  $queries
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    public static function buildWinterSitemapResults(array $urls, string $source, array $queries): array
    {
        $results = [];

        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?? '';

            if ($path === '') {
                continue;
            }

            $title = self::humanizeWinterTitle($path);
            $excerpt = 'Winter documentation page: '.trim(self::normalizeSearchText($path));
            $score = self::scoreResult($title, $excerpt, $url, $queries);

            if ($score <= 0) {
                continue;
            }

            $results[] = [
                'provider' => 'winter_cms',
                'source' => $source,
                'title' => $title,
                'url' => $url,
                'excerpt' => $excerpt,
                'score' => $score,
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $queries
     * @return array<int, string>
     */
    public static function expandQueries(array $queries, int $maxVariants = 8): array
    {
        $normalizedVariants = [];
        $filteredVariants = [];
        $tailBigramVariants = [];
        $trigramVariants = [];
        $bigramVariants = [];
        $tokenVariants = [];

        foreach ($queries as $query) {
            $normalized = self::normalizeSearchText($query);

            if ($normalized === '') {
                continue;
            }

            $normalizedVariants[] = $normalized;

            $tokens = self::collapseAdjacentRepeatedTokens(
                self::filterSearchTokens(self::tokenize($normalized))
            );

            if ($tokens === []) {
                continue;
            }

            $filteredVariants[] = implode(' ', $tokens);

            if (count($tokens) >= 2) {
                $tailBigramVariants[] = implode(' ', array_slice($tokens, -2));
            }

            foreach ([3, 2] as $windowSize) {
                $lastStart = count($tokens) - $windowSize;

                for ($index = 0; $index <= $lastStart; $index++) {
                    $variant = implode(' ', array_slice($tokens, $index, $windowSize));

                    if ($windowSize === 3) {
                        $trigramVariants[] = $variant;

                        continue;
                    }

                    $bigramVariants[] = $variant;
                }
            }

            foreach ($tokens as $token) {
                $tokenVariants[] = $token;
            }
        }

        $variants = [
            ...$normalizedVariants,
            ...$filteredVariants,
            ...$tailBigramVariants,
            ...$trigramVariants,
            ...$bigramVariants,
            ...$tokenVariants,
            'winter cms',
        ];

        return array_slice(self::uniqueOrdered(array_filter($variants, static fn (string $variant): bool => $variant !== '')), 0, $maxVariants);
    }

    /**
     * @param  array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score?:int}>  $results
     * @param  array<int, string>  $queries
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    public static function rankResults(array $results, array $queries, int $maxResults = 8): array
    {
        $deduplicated = [];

        foreach ($results as $result) {
            $score = isset($result['score']) && $result['score'] > 0
                ? $result['score']
                : self::scoreResult($result['title'], $result['excerpt'], $result['url'], $queries);

            if ($score <= 0) {
                continue;
            }

            $normalized = [
                'provider' => $result['provider'],
                'source' => $result['source'],
                'title' => $result['title'],
                'url' => $result['url'],
                'excerpt' => $result['excerpt'],
                'score' => $score,
            ];

            if (! isset($deduplicated[$normalized['url']]) || $score > $deduplicated[$normalized['url']]['score']) {
                $deduplicated[$normalized['url']] = $normalized;
            }
        }

        usort($deduplicated, static function (array $left, array $right): int {
            return [$right['score'], $left['title']] <=> [$left['score'], $right['title']];
        });

        return array_slice(array_values($deduplicated), 0, $maxResults);
    }

    /**
     * @param  array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>  $results
     * @param  array<int, string>  $queries
     * @param  array<int, string>  $sources
     */
    public static function formatMarkdown(array $results, array $queries, array $sources, int $characterLimit = 12000): string
    {
        if ($results === []) {
            return sprintf(
                "# Winter documentation search results\n\nNo results found for: %s\n\nSources searched: %s",
                self::formatInlineList($queries),
                self::formatInlineList($sources),
            );
        }

        $output = [
            '# Winter documentation search results',
            '',
            'Queries: '.self::formatInlineList($queries),
            'Sources: '.self::formatInlineList($sources),
        ];

        foreach ($results as $index => $result) {
            $entry = [
                '',
                sprintf('## %d. %s', $index + 1, $result['title']),
                sprintf('- Provider: `%s`', $result['provider']),
                sprintf('- Source: `%s`', $result['source']),
                sprintf('- URL: %s', $result['url']),
                sprintf('- Excerpt: %s', $result['excerpt']),
            ];

            $candidate = implode("\n", [...$output, ...$entry]);

            if (mb_strlen($candidate) > $characterLimit) {
                $output[] = '';
                $output[] = '_Results truncated to stay within the response size limit._';

                break;
            }

            $output = [...$output, ...$entry];
        }

        return implode("\n", $output);
    }

    public static function extractHiddenInputValue(string $html, string $name): ?string
    {
        $pattern = sprintf('/name="%s"[^>]*value="([^"]+)"/i', preg_quote($name, '/'));

        if (! preg_match($pattern, $html, $matches)) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
    }

    /**
     * @return array<int, array{provider:string,source:string,title:string,url:string,excerpt:string,score:int}>
     */
    private static function parseWinterSearchHtml(string $html, string $source): array
    {
        $results = [];

        preg_match_all('/<a\b[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));
            $anchorHtml = $match[2];
            preg_match_all('/<span\b[^>]*font-semibold[^>]*>(.*?)<\/span>/is', $anchorHtml, $titleMatches);
            preg_match('/<small\b[^>]*>(.*?)<\/small>/is', $anchorHtml, $excerptMatch);

            $title = self::normalizeHtmlText(end($titleMatches[1]) ?: '');
            $excerpt = self::normalizeHtmlText($excerptMatch[1] ?? '');

            if ($url === '') {
                continue;
            }

            if ($title === '') {
                $title = self::fallbackTitleFromUrl($url);
            }

            $results[] = [
                'provider' => 'winter_cms',
                'source' => $source,
                'title' => $title,
                'url' => $url,
                'excerpt' => $excerpt,
                'score' => 0,
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $queries
     */
    private static function scoreResult(string $title, string $excerpt, string $url, array $queries): int
    {
        $titleText = self::normalizeSearchText($title);
        $excerptText = self::normalizeSearchText($excerpt);
        $urlText = self::normalizeSearchText($url);
        $score = 0;

        foreach ($queries as $query) {
            $normalizedQuery = self::normalizeSearchText($query);

            if ($normalizedQuery === '') {
                continue;
            }

            if (str_contains($titleText, $normalizedQuery)) {
                $score += 160;
            }

            if (str_contains($excerptText, $normalizedQuery)) {
                $score += 80;
            }

            if (str_contains($urlText, str_replace(' ', '-', $normalizedQuery))) {
                $score += 40;
            }

            foreach (self::tokenize($normalizedQuery) as $token) {
                if (mb_strlen($token) < 2) {
                    continue;
                }

                if (str_contains($titleText, $token)) {
                    $score += 24;
                }

                if (str_contains($excerptText, $token)) {
                    $score += 12;
                }

                if (str_contains($urlText, $token)) {
                    $score += 6;
                }
            }
        }

        return $score;
    }

    private static function fallbackTitleFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $lastSegment = end($segments) ?: 'Documentation';

        return ucwords(str_replace(['-', '_'], ' ', $lastSegment));
    }

    private static function humanizeTwigTitle(string $path): string
    {
        $path = preg_replace('#^/doc/3\.x/#', '', $path) ?? $path;
        $path = preg_replace('#/(index)?\.html$#', '', $path) ?? $path;
        $path = trim($path, '/');

        if ($path === '') {
            return 'Twig documentation';
        }

        return ucwords(str_replace(['/', '-', '_'], ' ', $path));
    }

    private static function humanizeWinterTitle(string $path): string
    {
        $path = preg_replace('#^/docs/v1\.2/(docs|markup|ui|api)/#', '', $path) ?? $path;
        $path = trim($path, '/');

        if ($path === '') {
            return 'Winter documentation';
        }

        $segments = array_values(array_filter(explode('/', $path)));
        $importantSegments = array_slice($segments, -2);

        return ucwords(str_replace(['-', '_'], ' ', implode(' ', $importantSegments)));
    }

    /**
     * @param  array<int, string>  $values
     */
    private static function formatInlineList(array $values): string
    {
        return implode(', ', array_map(static fn (string $value): string => sprintf('`%s`', $value), $values));
    }

    private static function normalizeHtmlText(string $value): string
    {
        $decoded = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    /**
     * @return array<int, string>
     */
    private static function tokenize(string $value): array
    {
        $normalized = self::normalizeSearchText($value);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(preg_split('/\s+/u', $normalized) ?: []));
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private static function filterSearchTokens(array $tokens): array
    {
        $stopWords = [
            'a', 'an', 'and', 'class', 'cms', 'create', 'creating', 'default', 'docs', 'documentation',
            'for', 'how', 'htm', 'in', 'of', 'plugin', 'simple', 'the', 'to', 'tutorial', 'winter',
        ];

        return array_values(array_filter(
            array_map(static fn (string $token): string => self::normalizeSearchToken($token), $tokens),
            static fn (string $token): bool => $token !== '' && ! in_array($token, $stopWords, true)
        ));
    }

    private static function normalizeSearchText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        $value = preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $value) ?? $value;
        $value = str_replace(['#', '.', '/', '_', '-', '\\'], ' ', $value);
        $value = mb_strtolower($value);

        return trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value));
    }

    private static function normalizeSearchToken(string $token): string
    {
        $token = self::normalizeSearchText($token);

        if (str_ends_with($token, 'ies') && mb_strlen($token) > 4) {
            return mb_substr($token, 0, -3).'y';
        }

        if (str_ends_with($token, 's') && ! str_ends_with($token, 'ss') && mb_strlen($token) > 4) {
            return mb_substr($token, 0, -1);
        }

        return $token;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private static function collapseAdjacentRepeatedTokens(array $tokens): array
    {
        $collapsedTokens = [];

        foreach ($tokens as $token) {
            if ($token === '' || $token === end($collapsedTokens)) {
                continue;
            }

            $collapsedTokens[] = $token;
        }

        return $collapsedTokens;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private static function uniqueOrdered(array $values): array
    {
        $unique = [];

        foreach ($values as $value) {
            if (! isset($unique[$value])) {
                $unique[$value] = $value;
            }
        }

        return array_values($unique);
    }
}


