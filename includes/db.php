<?php

function db_statement(mysqli $conn, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $refs = [];
        foreach ($params as $key => &$value) {
            $refs[$key] = &$value;
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();

    return $stmt;
}

function db_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = db_statement($conn, $sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function db_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_statement($conn, $sql, $types, $params);
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function db_execute(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = db_statement($conn, $sql, $types, $params);
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    return $affectedRows;
}

function db_value(mysqli $conn, string $sql, string $types = '', array $params = [], $default = null)
{
    $row = db_fetch_one($conn, $sql, $types, $params);

    if ($row === null) {
        return $default;
    }

    return reset($row);
}
