<?php

declare(strict_types=1);

namespace LaravelHyperf\Cache\Contracts;

interface Lock
{
    /**
     * Attempt to acquire the lock.
     */
    public function get(?callable $callback = null): mixed;

    /**
     * Attempt to acquire the lock for the given number of seconds.
     */
    public function block(int $seconds, ?callable $callback = null): mixed;

    /**
     * Release the lock.
     */
    public function release(): bool;

    /**
     * Returns the current owner of the lock.
     */
    public function owner(): string;

    /**
     * Releases this lock in disregard of ownership.
     */
    public function forceRelease(): void;
}
