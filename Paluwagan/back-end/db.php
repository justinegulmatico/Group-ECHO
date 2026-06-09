<?php
// pdo db + tx stuff (for process files)

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private string $host = 'localhost';
    private string $user = 'root';
    private string $pass = '';
    private string $dbname = 'trustfund_db';
    private string $charset = 'utf8mb4';

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("db fail: " . $e->getMessage());
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // tx helpers

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    // rollback + log fail reason if given
    public function rollback(?string $reason = null): bool
    {
        $rolledBack = $this->pdo->rollBack();

        if ($reason) {
            $this->logFailedTransaction($reason);
        }

        return $rolledBack;
    }

    // tx wrapper (auto commit/rollback)
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this->pdo);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback("Exception in transaction: " . $e->getMessage());
            throw $e; // let caller handle msg
        }
    }

    // convenience stuff

    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // log failed tx
    private function logFailedTransaction(string $reason): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO failed_transaction_logs 
                (reason, script, user_id, created_at) 
                VALUES (:reason, :script, :user_id, NOW())
            ");
            $stmt->execute([
                ':reason'  => $reason,
                ':script'  => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
                ':user_id' => $_SESSION['user_id'] ?? null,
            ]);
        } catch (Exception $e) {
            error_log("log fail: " . $e->getMessage());
        }
    }

    // Prevent cloning / unserializing
    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}

// global helpers

function get_db(): PDO
{
    return Database::getInstance()->getPdo();
}

// quick tx wrapper
function db_transaction(callable $callback)
{
    return Database::getInstance()->transaction($callback);
}

function db_begin_transaction(): bool
{
    return Database::getInstance()->beginTransaction();
}

function db_commit(): bool
{
    return Database::getInstance()->commit();
}

function db_rollback(?string $reason = null): bool
{
    return Database::getInstance()->rollback($reason);
}

// legacy mysqli $conn for old code (index, controllers etc still use it)

if (!isset($conn) || !($conn instanceof mysqli)) {
    $host   = "localhost";
    $user   = "root";
    $pass   = "";
    $dbname = "trustfund_db";

    $conn = mysqli_connect($host, $user, $pass, $dbname);

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Optional: set charset for consistency with PDO
    mysqli_set_charset($conn, "utf8mb4");
}
?>