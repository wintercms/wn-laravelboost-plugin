<?php

namespace Winter\LaravelBoost\Console;

use Illuminate\Console\Command;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Winter\LaravelBoost\Classes\Tools\WinterDevelopmentGuide;
use Winter\LaravelBoost\Classes\Tools\WinterProjectOverview;
use Winter\LaravelBoost\Classes\Tools\WinterProjectStructure;
use Winter\LaravelBoost\Classes\Tools\WinterScaffoldingCommands;
use Winter\LaravelBoost\Classes\Tools\WinterViewStructure;

class TestMcpTools extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'winter:test-mcp {tool?}';

    /**
     * The console command description.
     */
    protected $description = 'Test Winter CMS MCP tools';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!class_exists(Request::class)) {
            $this->error('Laravel MCP package is not installed. Run: composer require laravel/mcp');
            return 1;
        }

        $tool = $this->argument('tool');

        $tools = [
            'overview' => WinterProjectOverview::class,
            'structure' => WinterProjectStructure::class,
            'scaffolding' => WinterScaffoldingCommands::class,
            'views' => WinterViewStructure::class,
            'guide' => WinterDevelopmentGuide::class,
        ];

        if ($tool && isset($tools[$tool])) {
            $this->testTool($tool, $tools[$tool]);
        } else {
            $this->info("Available tools: " . implode(', ', array_keys($tools)));
            $this->info("Usage: php artisan winter:test-mcp {tool}");
            $this->line('');

            foreach ($tools as $name => $toolClass) {
                $this->testTool($name, $toolClass);
                $this->line('');
            }
        }

        return 0;
    }

    protected function testTool(string $name, string $toolClass): void
    {
        $this->info("=== Testing {$name} tool ===");

        try {
            $tool = new $toolClass();

            // Create a mock request - the Winter tools don't use request parameters
            $request = $this->createMockRequest();
            $response = $tool->handle($request);

            $this->outputResponse($response);
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    protected function createMockRequest(): Request
    {
        // Create request via reflection since constructor may vary by version
        $reflection = new \ReflectionClass(Request::class);

        // Try common constructor patterns
        try {
            return new Request('tools/call', []);
        } catch (\Throwable $e) {
            // Fallback: create without constructor
            return $reflection->newInstanceWithoutConstructor();
        }
    }

    protected function outputResponse(Response $response): void
    {
        // Get content from response - it's a Content object with __toString
        $content = $response->content();

        // Content objects have __toString that returns the text/JSON
        $text = (string) $content;

        // Try to pretty-print if it's JSON
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->line(json_encode($decoded, JSON_PRETTY_PRINT));
        } else {
            $this->line($text);
        }
    }
}
