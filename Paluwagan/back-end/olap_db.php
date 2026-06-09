<?php
// olap db connection (separate warehouse)

class OlapDatabase
{
    private static ?OlapDatabase $instance = null;
    private ?PDO $pdo = null;

    private string $host = 'localhost';
    private string $user = 'root';
    private string $pass = '';
    private string $dbname = 'trustfund_olap';   // Separate OLAP database
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
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("OLAP Database connection failed: " . $e->getMessage() . 
                "\nPlease ensure 'trustfund_olap' database exists and run olap_schema.sql first.");
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // fresh conn helper (for etl)
    public static function getConnection(): PDO
    {
        return self::getInstance()->getPdo();
    }

    // safe query helper
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // tx for etl
    public static function transaction(callable $callback)
    {
        $pdo = self::getInstance()->getPdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
?>