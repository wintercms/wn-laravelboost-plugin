<?php

namespace Winter\LaravelBoost\Console;

use Illuminate\Console\Command;
use Winter\LaravelBoost\Classes\WinterMcpProvider;

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
        $tool = $this->argument('tool');
        $provider = new TestableWinterMcpProvider();

        $tools = [
            'overview' => 'getProjectOverview',
            'structure' => 'getProjectStructure',
            'scaffolding-commands' => 'getScaffoldingCommands',
            'scaffolding-discovery' => 'getScaffoldingDiscovery',
            'view-structure' => 'getViewStructure',
            'development-guide' => 'getDevelopmentGuide'
        ];

        if ($tool && isset($tools[$tool])) {
            $this->testTool($tool, $tools[$tool], $provider);
        } else {
            $this->info("Available tools: " . implode(', ', array_keys($tools)));
            $this->info("Usage: php artisan winter:test-mcp {tool}");

            foreach ($tools as $name => $method) {
                $this->testTool($name, $method, $provider);
                $this->line('');
            }
        }
    }

    protected function testTool($name, $method, $provider)
    {
        $this->info("=== Testing {$name} tool ===");

        try {
            $result = $provider->$method();
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}

class TestableWinterMcpProvider extends WinterMcpProvider
{
    // Expose protected methods for testing the consolidated tools
    public function getProjectOverview(): array { return parent::getProjectOverview(); }
    public function getProjectStructure(): array { return parent::getProjectStructure(); }
    public function getScaffoldingCommands(): array { return parent::getScaffoldingCommands(); }
    public function getScaffoldingDiscovery(): array { return parent::getScaffoldingDiscovery(); }
    public function getViewStructure(): array { return parent::getViewStructure(); }
    public function getDevelopmentGuide(): array { return parent::getDevelopmentGuide(); }
}