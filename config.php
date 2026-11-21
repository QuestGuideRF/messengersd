<?php
require_once __DIR__ . '/app/env.php';
loadEnvFile(__DIR__ . '/.env');
define("APP_URL", (string) env('APP_URL', 'http://localhost'));
define("APP_DOMAIN", (string) env('APP_DOMAIN', 'localhost'));
define("APP_DEBUG", (string) env('APP_DEBUG', '0') === '1');
define("DB_SERVER", (string) env('DB_HOST', 'localhost'));
define("DB_USERNAME", (string) env('DB_USER', 'root'));
define("DB_PASSWORD", (string) env('DB_PASS', ''));
define("DB_NAME", (string) env('DB_NAME', 'social_messenger_db'));
define("DB_TABLE_PREFIX", (string) env('DB_TABLE_PREFIX', ''));
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}
class Database
{
    private $conn;
    private string $prefix;
    public function __construct()
    {
        $this->conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        $this->prefix = DB_TABLE_PREFIX;
        if ($this->conn->connect_error) {
            if (APP_DEBUG) {
                die("Ошибка подключения к базе данных: " . $this->conn->connect_error);
            }
            die("Ошибка подключения к базе данных.");
        }
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_id'])) {
            $uid = (int) $_SESSION['user_id'];
            $usersTable = $this->t('users');
            $userCheck = $this->executeQuery(
                "SELECT id FROM {$usersTable} WHERE id = ? LIMIT 1",
                [$uid],
                'i'
            );
            if (is_string($userCheck)) {
                return;
            }
            $userRow = $userCheck->get_result()->fetch_assoc();
            if (!$userRow) {
                $_SESSION = [];
                @session_destroy();
                return;
            }
            $sid = session_id();
            $sessionsTable = $this->t('user_sessions');
            $check = $this->executeQuery(
                "SELECT is_active FROM {$sessionsTable} WHERE session_id = ? LIMIT 1",
                [$sid],
                's'
            );
            if (!is_string($check)) {
                $row = $check->get_result()->fetch_assoc();
                if ($row && (int)$row['is_active'] !== 1) {
                    $_SESSION = [];
                    @session_destroy();
                    return;
                }
            }
            $this->executeQuery(
                "UPDATE {$usersTable} SET last_seen = NOW(), is_online = 1 WHERE id = ?",
                [$uid],
                'i'
            );
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->executeQuery(
                "INSERT INTO {$sessionsTable} (user_id, session_id, ip, user_agent, created_at, last_seen, is_active)
                 VALUES (?, ?, ?, ?, NOW(), NOW(), 1)
                 ON DUPLICATE KEY UPDATE last_seen = NOW(), is_active = 1",
                [$uid, $sid, $ip, $ua],
                'isss'
            );
        }
    }
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    public function executeQuery($sql, $params = [], $types = "")
    {
        $result = $this->conn->prepare($sql);
        if (!$result) {
            return APP_DEBUG ? ("SQL error: " . $this->conn->error) : "SQL error";
        }
        if ($params) {
            $result->bind_param($types, ...$params);
        }
        if (!$result->execute()) {
            return APP_DEBUG ? ("Execution error: " . $result->error) : "Execution error";
        }
        return $result;
    }
    public function t(string $table): string
    {
        return $this->prefix . $table;
    }
    function validate($value)
    {
        return htmlspecialchars(trim(stripslashes($value)), ENT_QUOTES, 'UTF-8');
    }
    public function select($table, $columns = "*", $condition = "", $params = [], $types = "")
    {
        $table = $this->t((string) $table);
        $sql = "SELECT $columns FROM $table" . ($condition ? " WHERE $condition" : "");
        $result = $this->executeQuery($sql, $params, $types);
        if (is_string($result)) {
            return $result;
        }
        return $result->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function insert($table, $data)
    {
        $table = $this->t((string) $table);
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $types = str_repeat('s', count($data));
        $result = $this->executeQuery($sql, array_values($data), $types);
        if (is_string($result)) {
            return $result;
        }
        return $this->conn->insert_id;
    }
    public function update($table, $data, $condition = "", $params = [], $types = "")
    {
        $table = $this->t((string) $table);
        $set = implode(", ", array_map(function ($k) {
            return "$k = ?";
        }, array_keys($data)));
        $sql = "UPDATE $table SET $set" . ($condition ? " WHERE $condition" : "");
        $types = str_repeat('s', count($data)) . $types;
        $result = $this->executeQuery($sql, array_merge(array_values($data), $params), $types);
        if (is_string($result)) {
            return $result;
        }
        return $this->conn->affected_rows;
    }
    public function delete($table, $condition = "", $params = [], $types = "")
    {
        $table = $this->t((string) $table);
        $sql = "DELETE FROM $table" . ($condition ? " WHERE $condition" : "");
        $result = $this->executeQuery($sql, $params, $types);
        if (is_string($result)) {
            return $result;
        }
        return $this->conn->affected_rows;
    }
    public function hashPassword($password)
    {
        return hash_hmac('sha256', $password, 'iqbolshoh');
    }
    public function currentUserIsAdmin(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        $rows = $this->select('users', 'is_admin', 'id = ?', [$_SESSION['user_id']], 'i');
        if (is_string($rows) || empty($rows)) {
            return false;
        }
        return !empty($rows[0]['is_admin']);
    }
}