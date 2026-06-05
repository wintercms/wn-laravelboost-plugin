<?php

declare(strict_types=1);

namespace Winter\LaravelBoost\Classes\Support;

use Composer\InstalledVersions;

final class LaravelBoostCompatibility
{
    public const LEGACY_DOCUMENTATION_SOURCES_CONFIG_KEY = 'boost.documentation.sources';

    public static function shouldRegisterLegacyDocumentationSources(mixed $existingDocumentationSources = null, ?string $installedBoostVersion = null): bool
    {
        $normalizedVersion = self::normalizeBoostVersion($installedBoostVersion ?? self::installedBoostVersion());

        if ($normalizedVersion !== null) {
            return version_compare($normalizedVersion, '2.0.0', '<');
        }

        return is_array($existingDocumentationSources);
    }

    /**
     * @param  array<string, mixed>  $existingDocumentationSources
     * @param  array<string, mixed>  $winterDocumentationSources
     * @return array<string, mixed>
     */
    public static function mergeDocumentationSources(array $existingDocumentationSources, array $winterDocumentationSources): array
    {
        return array_replace_recursive($existingDocumentationSources, $winterDocumentationSources);
    }

    public static function installedBoostVersion(): ?string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled('laravel/boost')) {
            return null;
        }

        return InstalledVersions::getPrettyVersion('laravel/boost')
            ?? InstalledVersions::getVersion('laravel/boost');
    }

    public static function normalizeBoostVersion(?string $version): ?string
    {
        if ($version === null) {
            return null;
        }

        $normalizedVersion = trim($version);

        if ($normalizedVersion === '') {
            return null;
        }

        if (! preg_match('/(\d+)(?:\.(\d+))?(?:\.(\d+))?/', ltrim($normalizedVersion, 'vV'), $matches)) {
            return null;
        }

        return sprintf(
            '%d.%d.%d',
            (int) $matches[1],
            isset($matches[2]) ? (int) $matches[2] : 0,
            isset($matches[3]) ? (int) $matches[3] : 0,
        );
    }
}

