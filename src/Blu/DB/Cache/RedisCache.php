<?php
declare(strict_types=1);

namespace Blu\DB\Cache;

use Redis;

class RedisCache implements CacheInterface
{
    private static bool $enabled = true;
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    public function get(string $key): mixed
    {
        if (!self::$enabled) {
            return null;
        }

        $value = $this->redis->get($key);
        return $value ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        if (!self::$enabled) {
            return;
        }
        $this->redis->setex($key, $ttl, serialize($value));
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}
