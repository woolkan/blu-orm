<?php

namespace Blu\DB\Hydration;

class Hydrator
{
    public static function hydrate(string $class, array $data): object
    {
        return new $class(...$data);
    }
}
