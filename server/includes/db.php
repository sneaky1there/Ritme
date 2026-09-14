<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host='.config('DB_HOST').';port='.config('DB_PORT').';dbname='.config('DB_NAME').';charset=utf8mb4', config('DB_USER'), config('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql); $stmt->execute($params); return $stmt;
}
// Table and field names are internal constants; never pass user-supplied identifiers.
function upsert(string $table, array $data): void {
    $fields = array_keys($data);
    $quoted = array_map(fn($f) => '`'.$f.'`', $fields);
    $updates = array_map(fn($f) => '`'.$f.'`=VALUES(`'.$f.'`)', $fields);
    query('INSERT INTO `'.$table.'` ('.implode(',', $quoted).') VALUES ('.implode(',', array_fill(0, count($fields), '?')).') ON DUPLICATE KEY UPDATE '.implode(',', $updates), array_values($data));
}
