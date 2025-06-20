<?php
declare(strict_types=1);
require_once '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error. Please try again later.");
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    public function rollBack(): bool {
        return $this->pdo->rollBack();
    }
    
    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
    
    public function prepare(string $sql): PDOStatement {
        return $this->pdo->prepare($sql);
    }
    
    public function executeQuery(string $sql, array $params = []): PDOStatement {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch() ?: null;
    }
    
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert(string $table, array $data): string {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $this->executeQuery($sql, array_values($data));
        return $this->lastInsertId();
    }
    
    public function update(string $table, array $data, string $condition, array $params = []): int {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $condition";
        $stmt = $this->executeQuery($sql, array_merge($values, $params));
        
        return $stmt->rowCount();
    }
}