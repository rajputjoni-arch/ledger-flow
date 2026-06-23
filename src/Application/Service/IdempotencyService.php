<?php

namespace App\Application\Service;

use Psr\Cache\CacheItemPoolInterface;

final class IdempotencyService
{
    public function __construct(private CacheItemPoolInterface $cache)
    {
    }

    public function has(string $key): bool
    {
        return $this->cache->hasItem($key);
    }

    public function get(string $key): ?array
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }

    public function store(string $key, array $payload): void
    {
        $item = $this->cache->getItem($key);
        $item->set($payload);
        $item->expiresAfter(3600);
        $this->cache->save($item);
    }
}
