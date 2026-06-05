<?php

declare(strict_types=1);

namespace Winter\LaravelBoost\Classes\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use Winter\LaravelBoost\Classes\Tools\SearchWinterDocs;

class WinterDocsServer extends Server
{
    protected string $name = 'Winter Documentation';

    protected string $version = '1.0.0';

    protected string $instructions = 'Searches Winter CMS and Twig documentation for Winter-specific development tasks. Use this server when you need authoritative Winter CMS docs instead of generic Laravel documentation.';

    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        SearchWinterDocs::class,
    ];
}
