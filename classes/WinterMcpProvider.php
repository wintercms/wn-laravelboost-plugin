<?php

namespace Winter\LaravelBoost\Classes;

use Backend\Facades\Backend;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Server\McpServer;

/**
 * Winter CMS MCP Tools Provider
 *
 * Provides Winter CMS specific MCP tools for AI development assistance
 */
class WinterMcpProvider
{
    /**
     * Register Winter CMS specific MCP tools
     */
    public function registerTools(McpServer $server): void
    {
        // Winter CMS Project Overview (combines system info + theme info)
        $server->tool(
            'winter_project_overview',
            'Get Winter CMS project overview: version, environment, theme, and basic info',
            ['type' => 'object'],
            function () {
                return $this->getProjectOverview();
            }
        );

        // Winter CMS Project Structure (combines plugins + components + controllers)
        $server->tool(
            'winter_project_structure',
            'Get complete project structure: plugins, components, and backend controllers',
            ['type' => 'object'],
            function () {
                return $this->getProjectStructure();
            }
        );

        // Winter CMS Scaffolding Commands (KEEP - high impact)
        $server->tool(
            'winter_scaffolding_commands',
            'Get comprehensive guide to Winter CMS scaffolding commands for code generation',
            ['type' => 'object'],
            function () {
                return $this->getScaffoldingCommands();
            }
        );

        // Winter CMS Scaffolding Discovery (simplified console commands focused on scaffolding)
        $server->tool(
            'winter_scaffolding_discovery',
            'Discover available scaffolding commands with examples and usage',
            ['type' => 'object'],
            function () {
                return $this->getScaffoldingDiscovery();
            }
        );

        // Winter CMS View Structure (KEEP - unique value for dual view system)
        $server->tool(
            'winter_view_structure',
            'Map view files and understand Winter CMS dual view system (Twig frontend, PHP backend)',
            ['type' => 'object'],
            function () {
                return $this->getViewStructure();
            }
        );

        // Winter CMS Development Guide (combines service registry + best practices)
        $server->tool(
            'winter_development_guide',
            'Get essential Winter CMS development guidance: services, patterns, and best practices',
            ['type' => 'object'],
            function () {
                return $this->getDevelopmentGuide();
            }
        );
    }

    /**
     * Get Winter CMS project overview (combines system + theme info)
     */
    protected function getProjectOverview(): array
    {
        $overview = [
            'framework' => 'Winter CMS',
            'environment' => app()->environment(),
        ];

        // Get Winter CMS version information using proper Winter APIs
        try {
            if (class_exists('\System\Classes\UpdateManager')) {
                $updateManager = \System\Classes\UpdateManager::instance();
                $build = $updateManager->getBuildNumberManually();
                if (isset($build['build'])) {
                    $overview['winter_version'] = $build['build'];
                    $overview['winter_modified'] = $build['modified'] ?? false;
                    $overview['winter_confident'] = $build['confident'] ?? false;
                }
            }
        } catch (\Exception $e) {
            // Fallback to Artisan call if direct method fails
            try {
                \Artisan::call('winter:version', ['--only-version' => true]);
                $versionOutput = \Artisan::output();
                $overview['winter_version'] = trim($versionOutput);
            } catch (\Exception $e2) {
                // Version detection failed completely
            }
        }

        // Get theme information
        if (class_exists('\Cms\Classes\Theme')) {
            $activeTheme = \Cms\Classes\Theme::getActiveTheme();
            $overview['theme'] = [
                'active_theme' => $activeTheme ? $activeTheme->getId() : null,
                'theme_path' => $activeTheme ? $activeTheme->getPath() : null,
            ];
        }

        // Get basic counts
        if (class_exists('\System\Classes\PluginManager')) {
            $overview['plugin_count'] = count(\System\Classes\PluginManager::instance()->getPlugins());
        }

        if (class_exists('\Cms\Classes\ComponentManager')) {
            $overview['component_count'] = count(\Cms\Classes\ComponentManager::instance()->listComponents());
        }

        return $overview;
    }





    /**
     * Get console commands with their implementations
     */
    protected function getConsoleCommands(): array
    {
        $commands = [];

        try {
            // Get all registered commands via Artisan facade
            $allCommands = \Artisan::all();

            foreach ($allCommands as $name => $command) {
                $reflection = new \ReflectionClass($command);

                $commandInfo = [
                    'name' => $name,
                    'class' => get_class($command),
                    'file' => $reflection->getFileName(),
                    'description' => $command->getDescription(),
                ];

                // Try to determine if it's a Winter-specific command
                if (str_starts_with($name, 'winter:') ||
                    str_starts_with($name, 'plugin:') ||
                    str_starts_with($name, 'theme:') ||
                    str_contains($reflection->getFileName(), '/modules/')) {
                    $commandInfo['winter_specific'] = true;
                }

                // Flag scaffolding commands
                if (str_starts_with($name, 'create:') || str_starts_with($name, 'make:')) {
                    $commandInfo['is_scaffolding'] = true;
                    $commandInfo['category'] = 'scaffolding';
                }

                $commands[] = $commandInfo;
            }

        } catch (\Exception $e) {
            return ['error' => 'Could not discover console commands: ' . $e->getMessage()];
        }

        return [
            'count' => count($commands),
            'commands' => $commands
        ];
    }

    /**
     * Get Winter CMS service registry
     */
    protected function getServiceRegistry(): array
    {
        $services = [
            'core_services' => [
                'UpdateManager' => [
                    'class' => '\System\Classes\UpdateManager',
                    'location' => 'modules/system/classes/UpdateManager.php',
                    'singleton' => true,
                    'description' => 'Handles CMS install and update process'
                ],
                'PluginManager' => [
                    'class' => '\System\Classes\PluginManager',
                    'location' => 'modules/system/classes/PluginManager.php',
                    'singleton' => true,
                    'description' => 'Manages plugin registration and loading'
                ],
                'ComponentManager' => [
                    'class' => '\Cms\Classes\ComponentManager',
                    'location' => 'modules/cms/classes/ComponentManager.php',
                    'singleton' => true,
                    'description' => 'Manages CMS components'
                ],
                'ThemeManager' => [
                    'class' => '\Cms\Classes\ThemeManager',
                    'location' => 'modules/cms/classes/ThemeManager.php',
                    'singleton' => false,
                    'description' => 'Handles theme operations'
                ],
                'MediaLibrary' => [
                    'class' => '\System\Classes\MediaLibrary',
                    'location' => 'modules/system/classes/MediaLibrary.php',
                    'singleton' => true,
                    'description' => 'Manages media files'
                ]
            ],
            'backend_services' => [
                'BackendAuth' => [
                    'class' => '\Backend\Classes\AuthManager',
                    'location' => 'modules/backend/classes/AuthManager.php',
                    'singleton' => true,
                    'description' => 'Backend authentication management'
                ],
                'BackendMenu' => [
                    'class' => '\Backend\Classes\NavigationManager',
                    'location' => 'modules/backend/classes/NavigationManager.php',
                    'singleton' => true,
                    'description' => 'Backend navigation and menu management'
                ]
            ],
            'available_facades' => [
                'Backend' => '\Backend\Facades\Backend',
                'BackendAuth' => '\Backend\Facades\BackendAuth',
                'BackendMenu' => '\Backend\Facades\BackendMenu'
            ]
        ];

        // Check which services are actually available
        foreach ($services['core_services'] as $name => &$service) {
            $service['available'] = class_exists($service['class']);
        }

        foreach ($services['backend_services'] as $name => &$service) {
            $service['available'] = class_exists($service['class']);
        }

        return $services;
    }

    /**
     * Find classes by pattern across modules and plugins
     */
    protected function findClasses(string $pattern): array
    {
        $classes = [];
        $searchPaths = [
            'modules' => base_path('modules'),
            'plugins' => base_path('plugins')
        ];

        foreach ($searchPaths as $type => $basePath) {
            if (!is_dir($basePath)) continue;

            $phpFiles = glob($basePath . '/**/*.php', GLOB_BRACE);

            foreach ($phpFiles as $file) {
                try {
                    $content = file_get_contents($file);

                    // Extract namespace and class name
                    if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch) &&
                        preg_match('/class\s+(\w+)/', $content, $classMatch)) {

                        $namespace = trim($nsMatch[1]);
                        $className = $classMatch[1];
                        $fullClassName = $namespace . '\\' . $className;

                        // Apply pattern filter
                        if (empty($pattern) ||
                            str_contains(strtolower($fullClassName), strtolower($pattern)) ||
                            str_contains(strtolower($className), strtolower($pattern))) {

                            $classes[] = [
                                'class' => $fullClassName,
                                'short_name' => $className,
                                'namespace' => $namespace,
                                'file' => $file,
                                'type' => $type,
                                'location' => str_replace(base_path(), '', $file)
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Skip files that can't be read
                    continue;
                }
            }
        }

        return [
            'pattern' => $pattern,
            'count' => count($classes),
            'classes' => array_slice($classes, 0, 50) // Limit results
        ];
    }

    /**
     * Explore Winter CMS core modules
     */
    protected function exploreModules(): array
    {
        $modules = [];
        $modulesPath = base_path('modules');

        if (!is_dir($modulesPath)) {
            return ['error' => 'Modules directory not found'];
        }

        $moduleDirectories = ['system', 'backend', 'cms'];

        foreach ($moduleDirectories as $moduleName) {
            $modulePath = $modulesPath . '/' . $moduleName;

            if (!is_dir($modulePath)) continue;

            $module = [
                'name' => $moduleName,
                'path' => $modulePath,
                'structure' => []
            ];

            // Map key directories
            $keyDirs = ['classes', 'controllers', 'models', 'console', 'facades'];

            foreach ($keyDirs as $dir) {
                $dirPath = $modulePath . '/' . $dir;
                if (is_dir($dirPath)) {
                    $module['structure'][$dir] = [
                        'path' => $dirPath,
                        'files' => glob($dirPath . '/*.php') ?: [],
                        'subdirs' => glob($dirPath . '/*', GLOB_ONLYDIR) ?: []
                    ];
                }
            }

            // Find key classes for this module
            $module['key_classes'] = $this->getModuleKeyClasses($moduleName, $modulePath);

            $modules[$moduleName] = $module;
        }

        return [
            'modules_path' => $modulesPath,
            'modules' => $modules
        ];
    }

    /**
     * Get key classes for a specific module
     */
    protected function getModuleKeyClasses(string $moduleName, string $modulePath): array
    {
        $keyClasses = [];

        $classesPath = $modulePath . '/classes';
        if (is_dir($classesPath)) {
            $phpFiles = glob($classesPath . '/*.php');

            foreach ($phpFiles as $file) {
                $className = basename($file, '.php');
                $keyClasses[] = [
                    'name' => $className,
                    'file' => $file,
                    'namespace' => ucfirst($moduleName) . '\\Classes\\' . $className
                ];
            }
        }

        return array_slice($keyClasses, 0, 10); // Limit to key classes
    }

    /**
     * Get view structure across themes and plugins
     */
    protected function getViewStructure(): array
    {
        $viewStructure = [
            'frontend_views' => [
                'description' => 'Twig templates (.htm files)',
                'themes' => [],
                'plugin_components' => [],
                'plugin_partials' => []
            ],
            'backend_views' => [
                'description' => 'PHP views (.php files with <?= ?> syntax)',
                'controller_views' => [],
                'partials' => []
            ]
        ];

        // Map theme views
        $themesPath = base_path('themes');
        if (is_dir($themesPath)) {
            $themes = glob($themesPath . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($themes as $themePath) {
                $themeName = basename($themePath);
                $viewStructure['frontend_views']['themes'][$themeName] = [
                    'layouts' => count(glob($themePath . '/layouts/*.htm') ?: []),
                    'pages' => $this->countFilesRecursive($themePath . '/pages', '*.htm'),
                    'partials' => $this->countFilesRecursive($themePath . '/partials', '*.htm'),
                ];
            }
        }

        // Map plugin component templates
        // Path: plugins/author/pluginname/components/componentname/template.htm
        // Index:   0      1       2           3          4            5
        $pluginComponentViews = glob(base_path('plugins/*/*/components/*/*.htm')) ?: [];
        $basePath = base_path();
        foreach ($pluginComponentViews as $viewFile) {
            $relativePath = substr($viewFile, strlen($basePath));
            $pathParts = explode('/', trim($relativePath, '/'));
            if (count($pathParts) >= 6) {
                $plugin = $pathParts[1] . '.' . $pathParts[2];
                $component = $pathParts[4];
                $template = $pathParts[5];

                $viewStructure['frontend_views']['plugin_components'][] = [
                    'plugin' => $plugin,
                    'component' => $component,
                    'template' => $template,
                ];
            }
        }

        // Map plugin partials
        // Path: plugins/author/pluginname/partials/partial.htm
        // Index:   0      1       2          3        4
        $pluginPartials = glob(base_path('plugins/*/*/partials/*.htm')) ?: [];
        foreach ($pluginPartials as $partialFile) {
            $relativePath = substr($partialFile, strlen($basePath));
            $pathParts = explode('/', trim($relativePath, '/'));
            if (count($pathParts) >= 5) {
                $plugin = $pathParts[1] . '.' . $pathParts[2];
                $partial = $pathParts[4];

                $viewStructure['frontend_views']['plugin_partials'][] = [
                    'plugin' => $plugin,
                    'partial' => $partial,
                ];
            }
        }

        // Map backend controller views
        // Path: plugins/author/pluginname/controllers/controllername/view.php
        // Index:   0      1       2           3            4           5
        $controllerViews = glob(base_path('plugins/*/*/controllers/*/*.php')) ?: [];
        foreach ($controllerViews as $viewFile) {
            $relativePath = substr($viewFile, strlen($basePath));
            $pathParts = explode('/', trim($relativePath, '/'));
            if (count($pathParts) >= 6) {
                $plugin = $pathParts[1] . '.' . $pathParts[2];
                $controller = $pathParts[4];
                $view = basename($pathParts[5], '.php');

                $viewStructure['backend_views']['controller_views'][] = [
                    'plugin' => $plugin,
                    'controller' => $controller,
                    'view' => $view,
                    'is_partial' => str_starts_with($view, '_'),
                ];
            }
        }

        $viewStructure['conventions'] = [
            'frontend' => 'Use .htm files with Twig syntax',
            'backend' => 'Use .php files with <?= ?> short echo tags',
            'partials' => 'Prefix with underscore (_) for partial views',
        ];

        return $viewStructure;
    }

    /**
     * Recursively count files matching a pattern in a directory.
     */
    private function countFilesRecursive(string $directory, string $pattern): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $count = count(glob($directory . '/' . $pattern) ?: []);

        $subdirs = glob($directory . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($subdirs as $subdir) {
            $count += $this->countFilesRecursive($subdir, $pattern);
        }

        return $count;
    }

    /**
     * Analyze backend view patterns and conventions
     */
    protected function getBackendViews(): array
    {
        $analysis = [
            'conventions' => [
                'file_extension' => '.php',
                'syntax' => 'PHP with <?= ?> short tags preferred',
                'location' => 'plugins/*/controllers/*/',
                'partials' => 'Files starting with underscore (_)'
            ],
            'common_patterns' => [],
            'view_types' => [
                'index.php' => 'List/index views',
                'create.php' => 'Create form views',
                'update.php' => 'Edit form views',
                'preview.php' => 'Preview/detail views',
                '_*.php' => 'Partial views (toolbars, popups, etc.)'
            ],
            'examples' => []
        ];

        // Find examples of each view type
        $controllerViews = glob(base_path('plugins/*/*/controllers/*/*.php'));
        $examples = [];

        foreach ($controllerViews as $viewFile) {
            $filename = basename($viewFile);
            $relativePath = str_replace(base_path(), '', $viewFile);

            // Categorize by filename pattern
            if (!isset($examples[$filename]) && count($examples) < 20) {
                try {
                    $content = file_get_contents($viewFile);
                    $isShortTags = str_contains($content, '<?=');
                    $isLongTags = str_contains($content, '<?php echo');

                    $examples[$filename] = [
                        'file' => $relativePath,
                        'size' => strlen($content),
                        'uses_short_tags' => $isShortTags,
                        'uses_long_tags' => $isLongTags,
                        'sample_content' => substr($content, 0, 200) . (strlen($content) > 200 ? '...' : '')
                    ];
                } catch (\Exception $e) {
                    // Skip files that can't be read
                    continue;
                }
            }
        }

        $analysis['examples'] = $examples;

        // Analyze common patterns
        $shortTagCount = 0;
        $longTagCount = 0;
        $totalFiles = 0;

        foreach ($examples as $example) {
            $totalFiles++;
            if ($example['uses_short_tags']) $shortTagCount++;
            if ($example['uses_long_tags']) $longTagCount++;
        }

        $analysis['common_patterns'] = [
            'total_backend_views' => count($controllerViews),
            'analyzed_samples' => $totalFiles,
            'short_tag_usage' => $shortTagCount . '/' . $totalFiles . ' files',
            'long_tag_usage' => $longTagCount . '/' . $totalFiles . ' files',
            'preferred_syntax' => $shortTagCount > $longTagCount ? '<?= ?> (short tags)' : '<?php echo ?> (long tags)'
        ];

        return $analysis;
    }

    /**
     * Get comprehensive Winter CMS scaffolding commands guide
     */
    protected function getScaffoldingCommands(): array
    {
        $scaffoldingGuide = [
            'principle' => 'Always use scaffolding commands before creating files manually',
            'core_commands' => [
                'create:plugin' => [
                    'description' => 'Creates a complete plugin structure with all necessary files',
                    'syntax' => 'create:plugin <PluginName.PluginCode>',
                    'example' => 'create:plugin MyCompany.BlogExtension',
                    'generates' => ['Plugin.php', 'plugin.yaml', 'version.yaml', 'basic directory structure'],
                    'use_when' => 'Starting any new plugin development'
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
                    'generates' => ['Model.php', 'migration', 'controller (with --all)', 'seeder (with --all)'],
                    'use_when' => 'Creating any new database entity'
                ],
                'create:controller' => [
                    'description' => 'Creates backend controller with form/list behaviors',
                    'syntax' => 'create:controller [options] <plugin> <controller>',
                    'example' => 'create:controller --model=Post MyCompany.Blog Posts',
                    'options' => [
                        '--model=<Model>' => 'Associate with specific model',
                        '--layout=<layout>' => 'Set form layout (standard, sidebar, fancy)',
                        '--stubs' => 'Create view files for local overwrites'
                    ],
                    'generates' => ['Controller.php', 'config files', 'view templates'],
                    'use_when' => 'Creating backend admin interfaces'
                ],
                'create:component' => [
                    'description' => 'Creates frontend component with default template',
                    'syntax' => 'create:component <plugin> <component>',
                    'example' => 'create:component MyCompany.Blog PostList',
                    'generates' => ['Component.php', 'default.htm template'],
                    'use_when' => 'Creating frontend functionality'
                ],
                'create:migration' => [
                    'description' => 'Creates database migration file',
                    'syntax' => 'create:migration <plugin> <migration_name>',
                    'example' => 'create:migration MyCompany.Blog create_posts_table',
                    'generates' => ['Timestamped migration file'],
                    'use_when' => 'Making database schema changes'
                ],
                'create:command' => [
                    'description' => 'Creates console command',
                    'syntax' => 'create:command <plugin> <command>',
                    'example' => 'create:command MyCompany.Blog SyncPosts',
                    'generates' => ['Console command class'],
                    'use_when' => 'Creating artisan commands'
                ]
            ],
            'specialized_commands' => [
                'create:formwidget' => [
                    'description' => 'Creates custom backend form widget',
                    'use_when' => 'Custom form input types needed'
                ],
                'create:reportwidget' => [
                    'description' => 'Creates backend dashboard widget',
                    'use_when' => 'Adding dashboard functionality'
                ],
                'create:settings' => [
                    'description' => 'Creates settings model for configuration',
                    'use_when' => 'Plugin needs configuration options'
                ],
                'create:theme' => [
                    'description' => 'Creates theme structure',
                    'use_when' => 'Creating custom themes'
                ],
                'create:test' => [
                    'description' => 'Creates test class',
                    'use_when' => 'Adding automated tests'
                ]
            ],
            'common_workflows' => [
                'new_plugin_development' => [
                    'steps' => [
                        '1. create:plugin Namespace.PluginName',
                        '2. create:model --all Namespace.PluginName ModelName',
                        '3. create:component Namespace.PluginName ComponentName',
                        '4. Customize generated files'
                    ]
                ],
                'add_model_to_existing_plugin' => [
                    'steps' => [
                        '1. create:model --controller --seed Namespace.PluginName ModelName',
                        '2. Customize model relationships and validation',
                        '3. Configure backend controller form/list'
                    ]
                ],
                'add_frontend_functionality' => [
                    'steps' => [
                        '1. create:component Namespace.PluginName ComponentName',
                        '2. Implement component logic in onRun()',
                        '3. Customize component template'
                    ]
                ]
            ],
            'best_practices' => [
                'always_scaffold_first' => 'Use scaffolding commands before manual file creation',
                'follow_naming_conventions' => 'Use PascalCase for models/controllers, kebab-case for components',
                'use_comprehensive_options' => 'Use --all flag for models when building full CRUD',
                'leverage_stubs' => 'Use --stubs option for controllers when customizing views'
            ],
            'anti_patterns' => [
                'manual_plugin_creation' => 'Never create Plugin.php manually - use create:plugin',
                'manual_model_creation' => 'Never create models from scratch - use create:model',
                'manual_controller_setup' => 'Never create controller directories manually - use create:controller',
                'skipping_migrations' => 'Always generate migrations with models unless specifically not needed'
            ]
        ];

        return $scaffoldingGuide;
    }


    /**
     * Get comprehensive project structure: plugins, components, and controllers
     */
    protected function getProjectStructure(): array
    {
        if (!class_exists('\System\Classes\PluginManager')) {
            return ['error' => 'Winter CMS not available'];
        }

        $structure = [
            'plugins' => [],
            'components' => [],
            'controllers' => []
        ];

        $pluginManager = \System\Classes\PluginManager::instance();
        $plugins = $pluginManager->getPlugins();

        foreach ($plugins as $id => $plugin) {
            $pluginPath = $pluginManager->getPluginPath($id);

            // Plugin info
            $pluginInfo = [
                'id' => $id,
                'class' => get_class($plugin),
                'path' => $pluginPath,
                'disabled' => $pluginManager->isDisabled($id),
            ];

            // Get plugin details if available
            if (method_exists($plugin, 'pluginDetails')) {
                $details = $plugin->pluginDetails();
                $pluginInfo['name'] = $details['name'] ?? $id;
                $pluginInfo['description'] = $details['description'] ?? '';
                $pluginInfo['author'] = $details['author'] ?? '';
                $pluginInfo['version'] = $details['version'] ?? '';
            }

            $structure['plugins'][] = $pluginInfo;

            // Components from this plugin
            if (method_exists($plugin, 'registerComponents')) {
                try {
                    $components = $plugin->registerComponents();
                    foreach ($components as $componentClass => $componentAlias) {
                        $structure['components'][] = [
                            'plugin' => $id,
                            'alias' => $componentAlias,
                            'class' => $componentClass,
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip if component registration fails
                }
            }

            // Controllers from this plugin
            $controllersPath = $pluginPath . '/controllers';
            if (is_dir($controllersPath)) {
                $controllerFiles = glob($controllersPath . '/*.php');
                foreach ($controllerFiles as $file) {
                    $controllerName = basename($file, '.php');
                    $structure['controllers'][] = [
                        'plugin' => $id,
                        'controller' => $controllerName,
                        'path' => $file,
                        'class' => str_replace('/', '\\', ucfirst($id)) . '\\Controllers\\' . ucfirst($controllerName),
                    ];
                }
            }
        }

        // Add summary counts
        $structure['summary'] = [
            'plugin_count' => count($structure['plugins']),
            'component_count' => count($structure['components']),
            'controller_count' => count($structure['controllers'])
        ];

        return $structure;
    }

    /**
     * Get focused scaffolding discovery for Winter CMS development
     */
    protected function getScaffoldingDiscovery(): array
    {
        $scaffoldingCommands = [];

        try {
            // Get all registered commands via Artisan facade
            $allCommands = \Artisan::all();

            foreach ($allCommands as $name => $command) {
                // Focus only on scaffolding commands
                if (str_starts_with($name, 'create:') ||
                    str_starts_with($name, 'make:') ||
                    in_array($name, ['winter:install', 'plugin:install', 'theme:install'])) {

                    $scaffoldingCommands[] = [
                        'name' => $name,
                        'description' => $command->getDescription(),
                        'class' => get_class($command),
                    ];
                }
            }
        } catch (\Exception $e) {
            return ['error' => 'Failed to discover scaffolding commands: ' . $e->getMessage()];
        }

        return [
            'message' => 'Use these commands instead of manual file creation',
            'scaffolding_commands' => $scaffoldingCommands,
            'priority_commands' => [
                'create:plugin' => 'Always use for new plugins',
                'create:model' => 'Always use for new models',
                'create:controller' => 'Always use for new controllers',
                'create:component' => 'Always use for new components'
            ]
        ];
    }

    /**
     * Get development guide combining service registry and best practices
     */
    protected function getDevelopmentGuide(): array
    {
        $guide = [
            'winter_cms_patterns' => [
                'plugin_architecture' => 'Use plugin-based architecture for all features',
                'component_system' => 'Components for reusable frontend functionality',
                'backend_controllers' => 'Controllers for admin interface management',
                'service_registry' => 'Use Winter\'s service container for dependencies'
            ],
            'development_workflow' => [
                '1_scaffold_first' => 'Always use scaffolding commands before manual creation',
                '2_follow_conventions' => 'Follow Winter CMS naming and structure conventions',
                '3_use_proper_apis' => 'Use Winter CMS APIs instead of Laravel direct access',
                '4_test_thoroughly' => 'Test both frontend and backend functionality'
            ],
            'code_discovery_patterns' => [
                'use_winter_apis' => 'Use UpdateManager, PluginManager, ComponentManager classes',
                'avoid_shell_exec' => 'Never use shell_exec for Winter commands - use Artisan::call',
                'check_class_existence' => 'Always check if Winter classes exist before using',
                'prefer_documentation' => 'Use search_docs tool over code analysis for Twig patterns'
            ],
            'view_systems' => [
                'frontend_views' => 'Twig templates (.htm files) for frontend pages and layouts',
                'backend_views' => 'PHP templates (.php files) with <?= ?> syntax for admin interface',
                'component_partials' => 'Twig partials for component rendering',
                'mail_templates' => 'Twig templates for email content'
            ]
        ];

        // Add service registry information if available
        try {
            if (class_exists('\System\Classes\PluginManager')) {
                $guide['available_services'] = [
                    'plugin_manager' => 'Manage plugin installation and status',
                    'update_manager' => 'Handle system updates and version info',
                    'component_manager' => 'Register and manage components',
                    'theme_manager' => 'Handle theme activation and configuration'
                ];
            }
        } catch (\Exception $e) {
            // Continue without service registry info
        }

        return $guide;
    }
}