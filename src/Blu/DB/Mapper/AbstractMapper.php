<?php

namespace Blu\DB\Mapper;

use Blu\DB\Cache\CacheInterface;
use Blu\DB\Hydration\Extractor;
use Blu\DB\Hydration\Hydrator;
use Blu\DB\Query\QueryBuilder;

abstract class AbstractMapper
{
    protected static CacheInterface $cache;
    protected static string $table;
    protected static string $modelClass;

    public function __construct(CacheInterface $cache)
    {
        self::$cache = $cache;
    }

    public static function findById(string $id): ?object
    {
        $key = 'model:' . static::$modelClass . ':' . $id;
        $cached = self::$cache->get($key);
        if ($cached) {
            return $cached;
        }
        $qb = (new QueryBuilder())
            ->table(static::$table)
            ->where('id', $id);
        $data = $qb->first();
        if (!$data) {
            return null;
        }
        $model = Hydrator::hydrate(static::$modelClass, $data);
        self::$cache->set($key, $model);
        return $model;
    }

    public static function findAll(): array
    {
        $key = 'model:' . static::$modelClass . ':all';
        $cached = self::$cache->get($key);
        if ($cached) {
            return $cached;
        }
        $qb = (new QueryBuilder())->table(static::$table);
        $rows = $qb->get();
        $models = array_map(fn($row) => Hydrator::hydrate(static::$modelClass, $row), $rows);
        self::$cache->set($key, $models);
        return $models;
    }

    public static function save(object $model): void
    {
        $data = Extractor::extract($model);
        $qb = (new QueryBuilder())->table(static::$table);
        if (static::findById($data['id'])) {
            $qb->where('id', $data['id'])->update($data);
        } else {
            $qb->insert($data);
        }
        $key = 'model:' . static::$modelClass . ':' . $data['id'];
        self::$cache->delete($key);
    }

    public static function delete(object $model): void
    {
        $data = Extractor::extract($model);
        (new QueryBuilder())
            ->table(static::$table)
            ->where('id', $data['id'])
            ->delete();
        $key = 'model:' . static::$modelClass . ':' . $data['id'];
        self::$cache->delete($key);
    }
}
