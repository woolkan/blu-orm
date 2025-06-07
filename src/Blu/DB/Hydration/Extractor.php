<?php
declare(strict_types=1);

namespace Blu\DB\Hydration;

class Extractor
{
    public static function extract(object $object): array
    {
        return get_object_vars($object);
    }
}
