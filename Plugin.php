<?php

namespace Winter\LaravelBoost;

require_once __DIR__.'/classes/Support/WinterDocumentationSources.php';
require_once __DIR__.'/classes/Support/WinterDocumentationSearchParser.php';
require_once __DIR__.'/classes/Support/LaravelBoostCompatibility.php';
require_once __DIR__.'/classes/Tools/SearchWinterDocs.php';
require_once __DIR__.'/classes/Servers/WinterDocsServer.php';

use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use System\Classes\PluginBase;
use Winter\LaravelBoost\Classes\Servers\WinterDocsServer;
use Winter\LaravelBoost\Classes\Support\LaravelBoostCompatibility;
use Winter\LaravelBoost\Classes\Support\WinterDocumentationSources;
use Winter\LaravelBoost\Classes\Tools\SearchWinterDocs;
use Winter\LaravelBoost\Classes\Tools\WinterDevelopmentGuide;
use Winter\LaravelBoost\Classes\Tools\WinterProjectOverview;
use Winter\LaravelBoost\Classes\Tools\WinterProjectStructure;
use Winter\LaravelBoost\Classes\Tools\WinterScaffoldingCommands;
use Winter\LaravelBoost\Classes\Tools\WinterViewStructure;
use Winter\LaravelBoost\Console\TestMcpTools;

/**
 * LaravelBoost Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'winter.laravelboost::lang.plugin.name',
            'description' => 'winter.laravelboost::lang.plugin.description',
            'author'      => 'Winter',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register(): void
    {
        if (!$this->app->config->get('app.debug', false)) {
            return;
        }

        $this->app->register(BoostServiceProvider::class);
        $this->app->register(McpServiceProvider::class);
        $this->app->alias(Mcp::class, 'Mcp');

        $this->registerConsoleCommand('winter.test-mcp', TestMcpTools::class);
    }

    /**
     * Boot method, called right before the request route.
     */
    public function boot(): void
    {
        $this->registerWinterDocumentationServer();
        $this->registerWinterMcpTools();
        $this->extendBoostConfiguration();
    }

    /**
     * Register the dedicated Winter documentation MCP server.
     */
    protected function registerWinterDocumentationServer(): void
    {
        if (! $this->app->config->get('app.debug', false)) {
            return;
        }

        if (! class_exists(\Laravel\Mcp\Server::class)) {
            return;
        }

        Mcp::local('winter-docs', WinterDocsServer::class);
    }

    /**
     * Register Winter CMS specific MCP tools via Laravel Boost config
     */
    protected function registerWinterMcpTools(): void
    {
        // Register Winter CMS MCP tools via Laravel Boost's tool include config
        if (class_exists(\Laravel\Mcp\Server\Tool::class)) {
            $existingTools = config('boost.mcp.tools.include', []);

            $winterTools = [
                SearchWinterDocs::class,
                WinterProjectOverview::class,
                WinterProjectStructure::class,
                WinterScaffoldingCommands::class,
                WinterViewStructure::class,
                WinterDevelopmentGuide::class,
            ];

            config([
                'boost.mcp.tools.include' => array_merge($existingTools, $winterTools)
            ]);
        }
    }

    /**
     * Extend Laravel Boost configuration for Winter CMS
     */
    protected function extendBoostConfiguration(): void
    {
        // Extend configuration files with Winter CMS specific settings
        if (config('boost.enabled', false)) {
            $winterDocumentationSources = WinterDocumentationSources::all();

            config([
                'winter.laravelboost.documentation.sources' => $winterDocumentationSources,
            ]);

            $this->registerLegacyDocumentationSources($winterDocumentationSources);

            // Add Winter CMS specific guidelines
            config([
                'boost.guidelines.winter_cms' => [
                    'framework' => 'Winter CMS',
                    'conventions' => [
                        'plugins' => 'Use plugin-based architecture with namespace/pluginname structure',
                        'components' => 'Extend Cms\Classes\ComponentBase for frontend components',
                        'backend' => 'Use Backend\Classes\Controller with behavior traits',
                        'models' => 'Extend Winter CMS Model class, not Eloquent directly',
                        'migrations' => 'Use version.yaml for plugin migrations',
                        'themes' => 'Use .htm files with Twig templating',
                    ]
                ]
            ]);
        }
    }

    /**
     * Register Winter documentation sources for legacy Laravel Boost versions
     * that still resolve documentation sources from configuration.
     *
     * @param  array<string, array<string, mixed>>  $winterDocumentationSources
     */
    protected function registerLegacyDocumentationSources(array $winterDocumentationSources): void
    {
        $existingDocumentationSources = config(LaravelBoostCompatibility::LEGACY_DOCUMENTATION_SOURCES_CONFIG_KEY);

        if (! LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources($existingDocumentationSources)) {
            return;
        }

        config([
            LaravelBoostCompatibility::LEGACY_DOCUMENTATION_SOURCES_CONFIG_KEY => LaravelBoostCompatibility::mergeDocumentationSources(
                is_array($existingDocumentationSources) ? $existingDocumentationSources : [],
                $winterDocumentationSources,
            ),
        ]);
    }
}
