<?php

declare(strict_types=1);

namespace Winter\LaravelBoost\Classes\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class WinterDevelopmentGuide extends Tool
{
    protected string $description = 'Get essential Winter CMS development guidance: architecture patterns, services, and best practices.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $guide = [
            'architecture_patterns' => [
                'plugin_architecture' => 'All features should be implemented as plugins in plugins/author/pluginname/',
                'component_system' => 'Components extend Cms\\Classes\\ComponentBase for reusable frontend functionality',
                'backend_controllers' => 'Controllers extend Backend\\Classes\\Controller with behavior traits (FormController, ListController)',
                'models' => 'Models extend Winter\\Storm\\Database\\Model, not Eloquent directly',
            ],
            'development_workflow' => [
                '1_scaffold_first' => 'Always use create:plugin, create:model, create:controller commands',
                '2_follow_conventions' => 'Follow Winter CMS naming and directory structure conventions',
                '3_use_proper_apis' => 'Use Winter CMS APIs (PluginManager, ComponentManager) instead of Laravel direct access',
                '4_version_migrations' => 'Track migrations in updates/version.yaml, not Laravel migration files',
            ],
            'view_systems' => [
                'frontend_views' => 'Twig templates (.htm files) in themes/ or plugin components/',
                'backend_views' => 'PHP templates (.php files) with <?= ?> syntax in controllers/',
                'component_partials' => 'Twig partials in components/componentname/ directories',
            ],
            'core_services' => [
                'PluginManager' => '\\System\\Classes\\PluginManager::instance() - Manage plugins',
                'UpdateManager' => '\\System\\Classes\\UpdateManager::instance() - Handle updates and version info',
                'ComponentManager' => '\\Cms\\Classes\\ComponentManager::instance() - Register and manage components',
                'ThemeManager' => '\\Cms\\Classes\\ThemeManager - Handle theme operations',
            ],
            'backend_behaviors' => [
                'FormController' => 'Add form functionality with config_form.yaml',
                'ListController' => 'Add list/table functionality with config_list.yaml',
                'RelationController' => 'Manage related records with config_relation.yaml',
                'ImportExportController' => 'Add import/export with config_import_export.yaml',
            ],
        ];

        return Response::json($guide);
    }
}
