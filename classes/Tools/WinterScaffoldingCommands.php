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
class WinterScaffoldingCommands extends Tool
{
    protected string $description = 'Get comprehensive guide to Winter CMS scaffolding commands for code generation. Always use these before creating files manually.';

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
            'principle' => 'Always use scaffolding commands before creating files manually',
            'core_commands' => [
                'create:plugin' => [
                    'description' => 'Creates a complete plugin structure with all necessary files',
                    'syntax' => 'create:plugin <PluginName.PluginCode>',
                    'example' => 'create:plugin MyCompany.BlogExtension',
                    'generates' => ['Plugin.php', 'plugin.yaml', 'version.yaml', 'basic directory structure'],
                ],
                'create:model' => [
                    'description' => 'Creates model with optional controller, migration, seeder, factory',
                    'syntax' => 'create:model [options] <plugin> <model>',
                    'example' => 'create:model --all MyCompany.Blog Post',
                    'options' => [
                        '--all' => 'Generate controller, migration, seeder, and factory',
                        '--controller' => 'Create backend controller',
                        '--seed' => 'Create seeder',
                        '--factory' => 'Create model factory',
                        '--no-migration' => 'Skip migration file'
                    ],
                ],
                'create:controller' => [
                    'description' => 'Creates backend controller with form/list behaviors',
                    'syntax' => 'create:controller [options] <plugin> <controller>',
                    'example' => 'create:controller --model=Post MyCompany.Blog Posts',
                    'options' => [
                        '--model=<Model>' => 'Associate with specific model',
                        '--stubs' => 'Create view files for local overwrites'
                    ],
                ],
                'create:component' => [
                    'description' => 'Creates frontend component with default template',
                    'syntax' => 'create:component <plugin> <component>',
                    'example' => 'create:component MyCompany.Blog PostList',
                ],
                'create:migration' => [
                    'description' => 'Creates database migration file',
                    'syntax' => 'create:migration <plugin> <migration_name>',
                    'example' => 'create:migration MyCompany.Blog create_posts_table',
                ],
                'create:command' => [
                    'description' => 'Creates console command',
                    'syntax' => 'create:command <plugin> <command>',
                    'example' => 'create:command MyCompany.Blog SyncPosts',
                ],
            ],
            'specialized_commands' => [
                'create:formwidget' => 'Creates custom backend form widget',
                'create:reportwidget' => 'Creates backend dashboard widget',
                'create:settings' => 'Creates settings model for configuration',
                'create:theme' => 'Creates theme structure',
                'create:test' => 'Creates test class',
            ],
            'best_practices' => [
                'always_scaffold_first' => 'Use scaffolding commands before manual file creation',
                'use_comprehensive_options' => 'Use --all flag for models when building full CRUD',
            ],
        ];

        return Response::json($guide);
    }
}
