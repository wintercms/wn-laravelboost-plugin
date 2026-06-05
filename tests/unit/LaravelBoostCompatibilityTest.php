<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Winter\LaravelBoost\Classes\Support\LaravelBoostCompatibility;

require_once __DIR__.'/../../classes/Support/LaravelBoostCompatibility.php';

final class LaravelBoostCompatibilityTest extends TestCase
{
    public function testItRegistersLegacyDocumentationSourcesForBoostOneX(): void
    {
        $this->assertTrue(LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources([], 'v1.2.3'));
        $this->assertTrue(LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources([], '1.9.0'));
    }

    public function testItDoesNotRegisterLegacyDocumentationSourcesForBoostTwoXAndNewer(): void
    {
        $this->assertFalse(LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources([], 'v2.0.0'));
        $this->assertFalse(LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources([], '2.4.8'));
    }

    public function testItFallsBackToExistingLegacyConfigWhenVersionCannotBeResolved(): void
    {
        $this->assertTrue(LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources(['winter_cms' => []], 'dev-main'));
        $this->assertFalse(LaravelBoostCompatibility::shouldRegisterLegacyDocumentationSources(null, 'dev-main'));
    }

    public function testItMergesWinterDocumentationSourcesIntoExistingLegacySources(): void
    {
        $merged = LaravelBoostCompatibility::mergeDocumentationSources([
            'laravel' => [
                'name' => 'Laravel',
                'sections' => [
                    'docs' => 'https://laravel.com/docs',
                ],
            ],
            'winter_cms' => [
                'name' => 'Outdated Winter CMS',
                'sections' => [
                    'general' => 'https://example.com/outdated',
                ],
            ],
        ], [
            'winter_cms' => [
                'name' => 'Winter CMS',
                'sections' => [
                    'general' => 'https://wintercms.com/docs/v1.2/docs',
                    'api' => 'https://wintercms.com/docs/v1.2/api',
                ],
            ],
            'twig' => [
                'name' => 'Twig',
            ],
        ]);

        $this->assertSame('https://laravel.com/docs', $merged['laravel']['sections']['docs']);
        $this->assertSame('Winter CMS', $merged['winter_cms']['name']);
        $this->assertSame('https://wintercms.com/docs/v1.2/docs', $merged['winter_cms']['sections']['general']);
        $this->assertSame('https://wintercms.com/docs/v1.2/api', $merged['winter_cms']['sections']['api']);
        $this->assertSame('Twig', $merged['twig']['name']);
    }
}

