<?php

declare(strict_types=1);

namespace LaravelHyperf\Cache\Console;

use Hyperf\Command\Command;
use Hyperf\Support\Filesystem\Filesystem;
use LaravelHyperf\Cache\Contracts\Factory as CacheContract;
use LaravelHyperf\Cache\Contracts\Repository;
use LaravelHyperf\Support\Traits\HasLaravelStyleCommand;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ClearCommand extends Command
{
    use HasLaravelStyleCommand;

    /**
     * The console command name.
     */
    protected ?string $name = 'cache:clear';

    /**
     * The console command description.
     */
    protected string $description = 'Flush the application cache';

    /**
     * Execute the console command.
     */
    public function handle(): ?int
    {
        $this->app->get(EventDispatcherInterface::class)
            ->dispatch('cache:clearing', [$this->argument('store'), $this->tags()]);

        if (method_exists($this->cache(), 'flush')) {
            if (! $this->cache()->flush()) {
                $this->error('Failed to clear cache. Make sure you have the appropriate permissions.');
                return 1;
            }
        }

        $this->flushRuntime();

        $this->app->get(EventDispatcherInterface::class)
            ->dispatch('cache:cleared', [$this->argument('store'), $this->tags()]);

        $this->info('Application cache cleared successfully.');

        return 0;
    }

    /**
     * Get the cache instance for the command.
     */
    protected function cache(): Repository
    {
        $cache = $this->app->get(CacheContract::class)
            ->store($this->argument('store'));

        return empty($this->tags()) ? $cache : $cache->tags($this->tags());
    }

    /**
     * Flush the runtime cache directory.
     */
    protected function flushRuntime(): void
    {
        $this->app->get(Filesystem::class)
            ->deleteDirectory(BASE_PATH . '/runtime/container');
    }

    /**
     * Get the tags passed to the command.
     */
    protected function tags(): array
    {
        return array_filter(explode(',', $this->option('tags') ?? ''));
    }

    /**
     *  Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to clear'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear', null],
        ];
    }
}
