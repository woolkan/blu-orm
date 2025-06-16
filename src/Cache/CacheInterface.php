<?php
declare(strict_types=1);

namespace Blu\DB\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttl = 300): void;

    public function delete(string $key): void;
}