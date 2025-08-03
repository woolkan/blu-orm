<?php
declare(strict_types=1);
namespace Blu\DB\Query;

use Blu\DB\Storage\Connection;
use PDO;

class QueryBuilder
{
    private array $select = ['*'];
    private string $table = '';
    private array $where = [];
    private array $order = [];
    private ?int $limit = null;
    private array $joins = [];
    private array $bindings = [];

    private ?string $lastSql      = null;   // 👈 NEW
    private array   $lastBindings = [];     // 👈 NEW

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(string ...$fields): self
    {
        if ($fields) {
            $this->select = $fields;
        }
        return $this;
    }

    public function where(string $field, mixed $value, string $operator = '='): self
    {
        $param = ':' . $field . count($this->where);
        $this->where[] = "$field $operator $param";
        $this->bindings[$param] = $value;
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->order[] = "$field $direction";
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "JOIN $table ON $first $operator $second";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->compileSelect();
        $stmt = $this->execute($sql, $this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function insert(array $data): bool
    {
        $fields = array_keys($data);
        $params = array_map(fn($f) => ':' . $f, $fields);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $fields),
            implode(', ', $params)
        );
        $this->execute($sql, array_combine($params, array_values($data)));
        return true;
    }

    public function update(array $data): bool
    {
        $set = [];
        foreach ($data as $field => $value) {
            $param = ':' . $field;
            $set[] = "$field = $param";
            $this->bindings[$param] = $value;
        }
        $sql = sprintf(
            'UPDATE %s SET %s %s',
            $this->table,
            implode(', ', $set),
            $this->compileWhere()
        );
        $this->execute($sql, $this->bindings);
        return true;
    }

    public function delete(): bool
    {
        $sql = sprintf('DELETE FROM %s %s', $this->table, $this->compileWhere());
        $this->execute($sql, $this->bindings);
        return true;
    }

    /** Zwraca zapytanie z podstawionymi wartościami – tylko do debugowania */
    public function toDebugSql(): string
    {
        // jeśli zapytanie już się wykonało – zwracamy gotowe
        if ($this->lastSql !== null) {
            return self::interpolate($this->lastSql, $this->lastBindings);
        }

        // jeśli jeszcze NIE, spróbujmy sami złożyć SELECT
        $sql = $this->compileSelect();        // ta metoda masz już w klasie
        return self::interpolate($sql, $this->bindings);
    }

    /** Podstaw wartości za named-placeholders, używając PDO::quote */
    private static function interpolate(string $sql, array $bindings): string
    {
        $pdo = Connection::get();

        foreach ($bindings as $param => $value) {
            $replacement = match (true) {
                $value === null         => 'NULL',
                is_bool($value)         => $value ? '1' : '0',
                is_numeric($value)      => (string) $value,
                default                 => $pdo->quote((string) $value),
            };
            /*  \b gwarantuje, że podmieniamy dokładnie ten placeholder */
            $sql = preg_replace('/' . preg_quote($param, '/') . '\b/', $replacement, $sql);
        }
        return $sql;
    }

    private function compileSelect(): string
    {
        $sql = sprintf(
            'SELECT %s FROM %s %s %s %s',
            implode(', ', $this->select),
            $this->table,
            implode(' ', $this->joins),
            $this->compileWhere(),
            $this->compileOrder()
        );
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return $sql;
    }

    private function compileWhere(): string
    {
        return $this->where ? 'WHERE ' . implode(' AND ', $this->where) : '';
    }

    private function compileOrder(): string
    {
        return $this->order ? 'ORDER BY ' . implode(', ', $this->order) : '';
    }

    private function execute(string $sql, array $bindings): \PDOStatement
    {
        $this->lastSql      = $sql;
        $this->lastBindings = $bindings;

        $pdo = Connection::get();
        $stmt = $pdo->prepare($sql);
        foreach ($bindings as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        return $stmt;
    }
}
