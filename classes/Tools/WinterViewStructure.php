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
class WinterViewStructure extends Tool
{
    protected string $description = 'Map view files and understand Winter CMS dual view system: Twig (.htm) for frontend, PHP (.php) for backend.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $viewStructure = [
            'frontend_views' => [
                'description' => 'Twig templates (.htm files)',
                'themes' => [],
                'plugin_components' => [],
            ],
            'backend_views' => [
                'description' => 'PHP views (.php files with <?= ?> syntax)',
                'controller_views' => [],
            ],
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

        return Response::json($viewStructure);
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
}
