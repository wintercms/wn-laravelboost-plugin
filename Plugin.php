<?php

namespace Winter\LaravelBoost;

use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use System\Classes\PluginBase;
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
        $this->registerWinterMcpTools();
        $this->extendBoostConfiguration();
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
            // Add Winter CMS documentation sources
            config([
                'boost.documentation.sources.winter_cms' => [
                    'name' => 'Winter CMS',
                    'base_url' => 'https://wintercms.com/docs',
                    'version' => '1.2',
                    'sections' => [
                        'general' => '/v1.2/docs',
                        'markup' => '/v1.2/markup',
                        'ui' => '/v1.2/ui',
                        'api' => '/v1.2/api',
                    ]
                ],
                'boost.documentation.sources.twig' => [
                    'name' => 'Twig',
                    'base_url' => 'https://twig.symfony.com/doc',
                    'version' => '3.x',
                    'sections' => [
                        'templates' => '/3.x/templates.html',
                        'syntax' => '/3.x/syntax.html',
                        'filters' => '/3.x/filters/index.html',
                        'functions' => '/3.x/functions/index.html',
                        'tags' => '/3.x/tags/index.html',
                    ]
                ]
            ]);

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
}
