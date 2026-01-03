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
class WinterProjectStructure extends Tool
{
    protected string $description = 'Get complete Winter CMS project structure: plugins, components, and backend controllers.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        if (!class_exists(\System\Classes\PluginManager::class)) {
            return Response::json(['error' => 'Winter CMS not available']);
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

            $pluginInfo = [
                'id' => $id,
                'class' => get_class($plugin),
                'path' => $pluginPath,
                'disabled' => $pluginManager->isDisabled($id),
            ];

            if (method_exists($plugin, 'pluginDetails')) {
                $details = $plugin->pluginDetails();
                $pluginInfo['name'] = $details['name'] ?? $id;
                $pluginInfo['description'] = $details['description'] ?? '';
                $pluginInfo['author'] = $details['author'] ?? '';
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
                    ];
                }
            }
        }

        $structure['summary'] = [
            'plugin_count' => count($structure['plugins']),
            'component_count' => count($structure['components']),
            'controller_count' => count($structure['controllers'])
        ];

        return Response::json($structure);
    }
}
