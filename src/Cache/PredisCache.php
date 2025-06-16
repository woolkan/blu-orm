<?php

namespace Blu\DB\Cache;

use Predis\Client;

class PredisCache implements CacheInterface
{
    private static bool $enabled = true;
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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

        $value = $this->client->get($key);
        return $value !== null ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        if (!self::$enabled) {
            return;
        }
        $this->client->setex($key, $ttl, serialize($value));
    }

    public function delete(string $key): void
    {
        $this->client->del([$key]);
    }
}
