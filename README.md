# Laravel Boost for Winter CMS

This plugin integrates [Laravel Boost](https://github.com/laravel/boost) and [Laravel MCP](https://laravel.com/docs/12.x/mcp) into Winter CMS, providing AI development tools with Winter CMS-specific context and capabilities.

## Features

### Winter CMS MCP Tools

This plugin provides several MCP (Model Context Protocol) tools specifically designed for Winter CMS development:

- **`winter_plugin_list`**: Get detailed information about all installed plugins
- **`winter_theme_info`**: Get active theme information and configuration
- **`winter_component_list`**: List all registered components with their details
- **`winter_system_info`**: Get Winter CMS version and system information
- **`winter_backend_controllers`**: List backend controllers across all plugins

### Documentation Integration

The plugin registers Winter CMS documentation sources with Laravel Boost's search system, enabling AI tools to search Winter CMS documentation including:

- General documentation
- Markup/Twig documentation
- UI documentation
- API documentation

### AI Guidelines Enhancement

Extends Laravel Boost's AI guidelines with Winter CMS-specific conventions:

- Plugin architecture patterns
- Component development best practices
- Backend controller conventions
- Model and migration patterns
- Theme and Twig usage guidelines

## Installation

1. Install Laravel Boost and Laravel MCP packages:
```bash
composer require laravel/boost laravel/mcp
```

2. The plugin will automatically register with Winter CMS once the dependencies are available.

## Usage

Once installed, AI development tools that support MCP (like Claude Code) will have access to Winter CMS-specific context and can search Winter CMS documentation directly.

The plugin automatically:
- Registers Winter CMS documentation sources
- Provides MCP tools for inspecting the Winter CMS installation
- Extends AI guidelines with Winter CMS conventions

## Requirements

- Winter CMS 1.3+
- PHP 8.1+
- Laravel Boost package
- Laravel MCP package

## AI Development Benefits

This plugin significantly improves AI-assisted development by:

1. **Contextual Awareness**: AI tools understand Winter CMS structure and conventions
2. **Documentation Access**: Direct search of Winter CMS documentation
3. **Project Inspection**: Tools can analyze plugin structure, themes, and components
4. **Best Practices**: AI follows Winter CMS conventions instead of generic Laravel patterns

## Development

This plugin follows Winter CMS plugin conventions:

- Main functionality in `Plugin.php`
- MCP provider in `classes/WinterMcpProvider.php`
- Language files in `lang/` directory
- Version tracking in `updates/version.yaml`