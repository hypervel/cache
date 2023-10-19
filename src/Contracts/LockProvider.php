<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Cache\Contracts;

interface LockProvider
{
    /**
     * Get a lock instance.
     */
    public function lock(string $name, int $seconds = 0, ?string $owner = null): Lock;

    /**
     * Restore a lock instance using the owner identifier.
     */
    public function restoreLock(string $name, string $owner): Lock;
}
