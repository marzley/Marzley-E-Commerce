<?php
declare(strict_types=1);

/**
 * mtkenya.php
 * mysqli helper that ensures the 'mtkenya' database exists (imports mtkenya.sql if needed)
 * Place mtkenya.sql in the same folder as this file.
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');    // adjust for your XAMPP user
define('DB_PASS', '');        // adjust for your XAMPP password
define('DB_NAME', 'mtkenya');
define('DB_CHARSET', 'utf8mb4');
define('SQL_INIT_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'mtkenya.sql');

function import_sql_file(mysqli $conn, string $file): bool {
    if (!is_readable($file)) return false;
    $sql = file_get_contents($file);
    if ($sql === false) return false;

    // remove C-style and line comments for safer multi_query
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
    $lines = preg_split("/\n/", $sql);
    $filtered = [];
    foreach ($lines as $line) {
        $line = preg_replace('/^\s*--.*$/', '', $line); // strip -- comments
        $filtered[] = $line;
    }
    $sql = implode("\n", $filtered);

    // Execute multiple statements
    if (!$conn->multi_query($sql)) {
        error_log('SQL import error: ' . $conn->error);
        return false;
    }
    // flush multi_query results
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    return true;
}

/**
 * Connect to MySQL and ensure DB exists. Returns mysqli or null.
 */
function db_connect(): ?mysqli {
    // try connecting directly to database
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
        // Unknown database? try creating it by connecting without DB
        if (stripos($mysqli->connect_error ?? '', 'Unknown database') !== false
            || stripos($mysqli->connect_error ?? '', 'Unknown database') !== false
        ) {
            // connect without database
            $tmp = new mysqli(DB_HOST, DB_USER, DB_PASS);
            if ($tmp->connect_errno) {
                error_log('DB connect error (no-db): ' . $tmp->connect_error);
                return null;
            }
            // Attempt to import SQL init file which should create the database and schema
            if (is_readable(SQL_INIT_FILE)) {
                if (!import_sql_file($tmp, SQL_INIT_FILE)) {
                    error_log('Failed to import SQL initialization file: ' . SQL_INIT_FILE);
                    $tmp->close();
                    return null;
                }
            } else {
                // fallback: create database minimally
                if (!$tmp->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET)) {
                    error_log('Failed to create database: ' . $tmp->error);
                    $tmp->close();
                    return null;
                }
            }
            $tmp->close();
            // try reconnecting to the newly created database
            $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($mysqli->connect_errno) {
                error_log('DB connect error after create: ' . $mysqli->connect_error);
                return null;
            }
        } else {
            error_log('DB connect error: ' . $mysqli->connect_error);
            return null;
        }
    }

    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}

/**
 * Run a query. If $types is provided, a prepared statement is used.
 * Returns array (for SELECT), boolean/assoc meta for non-select, or false on error.
 */
function db_query(string $sql, ?string $types = null, array $params = []) {
    $db = db_connect();
    if (!$db) return false;

    if ($types === null) {
        $res = $db->query($sql);
        if ($res === false) { error_log('Query error: ' . $db->error); $db->close(); return false; }
        if ($res === true) {
            $meta = ['affected_rows' => $db->affected_rows, 'insert_id' => $db->insert_id];
            $db->close();
            return $meta;
        }
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
        $db->close();
        return $rows;
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) { error_log('Prepare error: ' . $db->error); $db->close(); return false; }
    if ($types !== '' && !empty($params)) {
        // bind parameters (must be variables)
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    if (!$stmt->execute()) { error_log('Execute error: ' . $stmt->error); $stmt->close(); $db->close(); return false; }

    $result = $stmt->get_result();

    // mysqli::get_result() returns false for statements that do not produce a result set (INSERT/UPDATE/DELETE).
    // Previously code checked for null which caused fetch_all() to be called on a boolean.
    if ($result === false) {
        $meta = ['affected_rows' => $stmt->affected_rows, 'insert_id' => $db->insert_id];
        $stmt->close();
        $db->close();
        return $meta;
    }

    // $result is a mysqli_result object for SELECT queries
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    $db->close();
    return $rows;
}

function db_escape(string $val): string {
    $db = db_connect();
    if (!$db) return '';
    $escaped = $db->real_escape_string($val);
    $db->close();
    return $escaped;
}
?>