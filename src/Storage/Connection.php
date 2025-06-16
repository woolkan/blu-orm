<?php

namespace Blu\DB\Storage;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $pdo = null;

    public static function configure(string $dsn, string $user = '', string $password = '', array $options = []): void
    {
        self::$pdo = new PDO($dsn, $user, $password, $options);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function get(): PDO
    {
        if (!self::$pdo) {
            throw new PDOException('Database connection not configured');
        }

        return self::$pdo;
    }
}
