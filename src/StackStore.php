<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Cache;

use Carbon\Carbon;
use Closure;
use SwooleTW\Hyperf\Cache\Contracts\Store;

class StackStore implements Store
{
    /**
     * @param array<Store> $stores
     */
    public function __construct(protected array $stores) {}

    public function get(string $key): mixed
    {
        $record = $this->getOrRestoreRecord($key);

        return $record['value'] ?? null;
    }

    public function many(array $keys): array
    {
        return array_map(fn ($key) => $this->get($key), array_combine($keys, $keys));
    }

    public function put(string $key, mixed $value, int $seconds): bool
    {
        $record = [
            'value' => $value,
            'ttl' => $seconds,
        ];

        return $this->putRecord($key, $record);
    }

    public function putMany(array $values, int $seconds): bool
    {
        foreach ($values as $key => $value) {
            if (! $this->put($key, $value, $seconds)) {
                return false;
            }
        }

        return true;
    }

    public function increment(string $key, int $value = 1): int|bool
    {
        $record = $this->getOrRestoreRecord($key);

        if (is_null($record)) {
            return tap($value, fn ($value) => $this->forever($key, $value));
        }

        $newValue = $record['value'] + $value;
        $newRecord = ['value' => $newValue] + $record;

        if ($this->putRecord($key, $newRecord)) {
            return $newValue;
        }

        return false;
    }

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, $value * -1);
    }

    public function forever(string $key, mixed $value): bool
    {
        $record = compact('value');

        return $this->callStores(
            fn (Store $store) => $store->forever($key, $record),
        );
    }

    public function forget(string $key): bool
    {
        return $this->callStores(
            fn (Store $store) => $store->forget($key),
        );
    }

    public function flush(): bool
    {
        return $this->callStores(
            static fn (Store $store) => $store->flush(),
        );
    }

    public function getPrefix(): string
    {
        return '';
    }

    protected function getOrRestoreRecord(string $key): mixed
    {
        return $this->callStoresStacked(
            function (Store $store, Closure $next) use ($key): ?array {
                if (! is_null($record = $store->get($key))) {
                    return (array) $record;
                }

                if (is_null($record = $next())) {
                    return null;
                }

                if ($this->putToStore($store, $key, $record)) {
                    return $record;
                }

                return null;
            },
            static fn () => null
        );
    }

    protected function putRecord(string $key, array $record): bool
    {
        return $this->callStores(
            fn (Store $store) => $this->putToStore($store, $key, $record),
        );
    }

    protected function putToStore(Store $store, string $key, array $record): bool
    {
        if (! array_key_exists('value', $record)) {
            return false;
        }

        if (! array_key_exists('expiration', $record) && ! array_key_exists('ttl', $record)) {
            return $store->forever($key, $record);
        }

        $currentTimestamp = Carbon::now()->getTimestamp();
        $value = $record['value'];
        $expiration = $record['expiration'] ?? $currentTimestamp + $record['ttl'];
        $ttl = $record['ttl'] ?? $record['expiration'] - $currentTimestamp;
        $normalizedRecord = compact('value', 'expiration');

        return $store->put($key, $normalizedRecord, $ttl);
    }

    protected function callStoresStacked(Closure $handler, Closure $bottomLayer): mixed
    {
        return array_reduce(array_reverse($this->stores), function ($stack, $store) use ($handler) {
            return function () use ($stack, $store, $handler) {
                return $handler($store, $stack);
            };
        }, $bottomLayer)();
    }

    protected function callStores(Closure $handler): bool
    {
        foreach ($this->stores as $store) {
            if (! $handler($store)) {
                return false;
            }
        }

        return true;
    }
}
