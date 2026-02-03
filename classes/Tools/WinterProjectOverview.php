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
class WinterProjectOverview extends Tool
{
    protected string $description = 'Get Winter CMS project overview: version, environment, active theme, and plugin/component counts.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $overview = [
            'framework' => 'Winter CMS',
            'environment' => app()->environment(),
        ];

        // Get Winter CMS version information
        try {
            if (class_exists(\System\Classes\UpdateManager::class)) {
                $updateManager = \System\Classes\UpdateManager::instance();
                $build = $updateManager->getBuildNumberManually();
                if (isset($build['build'])) {
                    $overview['winter_version'] = $build['build'];
                    $overview['winter_modified'] = $build['modified'] ?? false;
                }
            }
        } catch (\Exception $e) {
            try {
                \Artisan::call('winter:version', ['--only-version' => true]);
                $overview['winter_version'] = trim(\Artisan::output());
            } catch (\Exception $e2) {
                $overview['winter_version'] = 'unknown';
            }
        }

        // Get theme information
        if (class_exists(\Cms\Classes\Theme::class)) {
            $activeTheme = \Cms\Classes\Theme::getActiveTheme();
            $overview['theme'] = [
                'active_theme' => $activeTheme ? $activeTheme->getId() : null,
                'theme_path' => $activeTheme ? $activeTheme->getPath() : null,
            ];
        }

        // Get basic counts
        if (class_exists(\System\Classes\PluginManager::class)) {
            $overview['plugin_count'] = count(\System\Classes\PluginManager::instance()->getPlugins());
        }

        if (class_exists(\Cms\Classes\ComponentManager::class)) {
            $overview['component_count'] = count(\Cms\Classes\ComponentManager::instance()->listComponents());
        }

        return Response::json($overview);
    }
}
