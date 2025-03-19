<?php

declare(strict_types=1);

namespace Hypervel\Cache;

use Hyperf\Context\ApplicationContext;
use Hypervel\Cache\Exceptions\InvalidArgumentException;

/**
 * Get / set the specified cache value.
 *
 * If an array is passed, we'll assume you want to put to the cache.
 *
 * @param null|array<string, mixed>|string $key key|data
 * @param mixed $default default|expiration|null
 * @return ($key is null ? \Hypervel\Cache\CacheManager : ($key is string ? mixed : bool))
 *
 * @throws InvalidArgumentException
 */
function cache($key = null, $default = null)
{
    $manager = ApplicationContext::getContainer()->get(CacheManager::class);

    if (is_null($key)) {
        return $manager;
    }

    if (is_string($key)) {
        return $manager->get($key, $default);
    }

    if (! is_array($key)) {
        throw new InvalidArgumentException(
            'When setting a value in the cache, you must pass an array of key / value pairs.'
        );
    }

    return $manager->put(key($key), reset($key), $default);
}
