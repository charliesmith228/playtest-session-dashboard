<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct(
        string $host,
        int $port,
        string $name,
        string $user,
        string $password
    )
    {
        try
        {
            // Data Source Name - the connection string
            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

            $this->connection = new PDO(
                $dsn,
                $user,
                $password,
                [
                    // Throw exceptions on database errors instead of returning false
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // Return rows as associative arrays by default
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }
        catch (PDOException $e)
        {
            // Don't expose the real error to the client - log it instead
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            die;
        }
    }

    // Run a SELECT query and return all matching rows
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Run an INSERT, UPDATE, or DELETE query
    // Returns the number of affected rows
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Get the ID of the last inserted row (useful after an INSERT)
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}