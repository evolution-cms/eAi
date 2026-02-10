<?php

namespace EvolutionCMS\eAi;

use EvolutionCMS\ServiceProvider;

class eAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadPluginsFrom(dirname(__DIR__) . '/plugins/');
        $this->registerFoundationShims();
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/eAiSettings.php', 'cms.settings.eAi');
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/ai.php', 'ai');

        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishResources();
        }

        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                $this->flattenPublishDirectories();
            }
        });
    }

    protected function publishResources(): void
    {
        $this->publishes([
            dirname(__DIR__) . '/config/eAiSettings.php' => config_path('cms/settings/eAi.php', true),
        ], 'eai-config');

        $this->publishes([
            dirname(__DIR__) . '/config/ai.php' => config_path('ai.php', true),
        ], 'eai-ai-config');

        $stubsPath = dirname(__DIR__) . '/stubs';
        if (is_dir($stubsPath)) {
            $this->publishes([
                $stubsPath . '/agent.stub' => base_path('stubs/agent.stub'),
                $stubsPath . '/structured-agent.stub' => base_path('stubs/structured-agent.stub'),
                $stubsPath . '/tool.stub' => base_path('stubs/tool.stub'),
            ], 'eai-stubs');
        }
    }

    protected function registerFoundationShims(): void
    {
        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Queue\\Queueable',
            \EvolutionCMS\eAi\Foundation\Queue\Queueable::class
        );

        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Bus\\PendingDispatch',
            \EvolutionCMS\eAi\Foundation\Bus\PendingDispatch::class
        );

        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Bus\\Dispatchable',
            \EvolutionCMS\eAi\Foundation\Bus\Dispatchable::class
        );
    }

    protected function aliasIfMissing(string $alias, string $target): void
    {
        if (class_exists($alias)) {
            return;
        }

        if (class_exists($target)) {
            class_alias($target, $alias);
        }
    }

    protected function flattenPublishDirectories(): void
    {
        if (!class_exists(\Illuminate\Support\ServiceProvider::class)) {
            return;
        }

        $reflection = new \ReflectionClass(\Illuminate\Support\ServiceProvider::class);
        $publishesProperty = $reflection->getProperty('publishes');
        $publishesProperty->setAccessible(true);
        $publishGroupsProperty = $reflection->getProperty('publishGroups');
        $publishGroupsProperty->setAccessible(true);

        $publishes = $publishesProperty->getValue();
        $publishGroups = $publishGroupsProperty->getValue();

        foreach ($publishes as $provider => $paths) {
            $publishes[$provider] = $this->expandPublishPaths($paths);
        }

        foreach ($publishGroups as $group => $paths) {
            $publishGroups[$group] = $this->expandPublishPaths($paths);
        }

        $publishesProperty->setValue(null, $publishes);
        $publishGroupsProperty->setValue(null, $publishGroups);
    }

    protected function expandPublishPaths(array $paths): array
    {
        $expanded = [];

        foreach ($paths as $from => $to) {
            if (is_dir($from)) {
                $files = $this->collectPublishFiles($from, $to);
                if ($files !== []) {
                    $expanded = array_merge($expanded, $files);
                    continue;
                }
            }
            $expanded[$from] = $to;
        }

        return $expanded;
    }

    protected function collectPublishFiles(string $sourceDir, string $targetDir): array
    {
        if (!is_dir($sourceDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
        );

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            $relative = substr($path, strlen($sourceDir) + 1);
            $files[$path] = $targetDir . DIRECTORY_SEPARATOR . $relative;
        }

        return $files;
    }
}
