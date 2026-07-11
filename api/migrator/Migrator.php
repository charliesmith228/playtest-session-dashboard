<?php

declare(strict_types=1);

namespace App\Migrator;

use App\Database;

class Migrator
{
    public function __construct(protected Database $database)
    {
        // Alway check this on object instantiation
        $this->checkMigrationsTableExists();
    }

    // Apply all migrations that haven't been applied
    public function migrate(): void
    {
        $pendingMigrations = $this->getPendingMigrations();

        if (empty($pendingMigrations)) {
            echo "Migrations up to date." . PHP_EOL;
            return;
        }

        foreach ($pendingMigrations as $pendingMigrations) {
            $this->runMigrationUp($pendingMigrations);
        }
    }

    // Reverse the most recently applied migration
    public function reverse(): void
    {
        $lastMigration = $this->database->queryOne("
            SELECT migration_name FROM migrations ORDER BY created DESC LIMIT 1
        ");

        if ($lastMigration === false) {
            echo "No migrations applied to reverse." . PHP_EOL;
            return;
        }

        $this->runMigrationDown($lastMigration["migration_name"]);
    }

    // Rollback all applied migrations
    public function rollback(): void
    {
        $appliedMigrations = $this->database->query(
            "SELECT migration_name FROM migrations ORDER BY created desc",
        );

        if (empty($appliedMigrations)) {
            echo "No migrations applied to roll back." . PHP_EOL;
            return;
        }

        foreach ($appliedMigrations as $appliedMigration) {
            $this->runMigrationDown($appliedMigration["migration_name"]);
        }
    }

    // Check that the migrations tracking table exists
    // If it doesn't the query will create it
    // This is needed to track applied migrations
    private function checkMigrationsTableExists(): void
    {
        $this->database->execute("
            CREATE TABLE IF NOT EXISTS migrations (
                ID SERIAL PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                created TIMESTAMP NOT NULL DEFAULT NOW()
            )
        ");
    }

    // Returns an array of migrations that have not yet been applied
    // Migrations files being with timestamp, and so files are sorted
    // to ensure latest migrations have been applied
    private function getPendingMigrations(): array
    {
        // Get all migrations from the migrations directory
        $migrationFiles = glob(__DIR__ . "/migrations/*.php");
        // Sort alphanumerically to ensure migrations are in chronological order
        sort($migrationFiles);

        //Get already applied migrations
        $appliedMigrations = $this->database->query("SELECT migration_name FROM migrations");
        $appliedMigrationsFileNames = array_column($appliedMigrations, "migration_name");

        //Return only those that have not been applied (i.e. pending)
        return array_filter($migrationFiles, static function (string $migrationFile) use ($appliedMigrationsFileNames) {
            return !\in_array(basename($migrationFile), $appliedMigrationsFileNames, strict: true);
        });
    }

    // Load migration class and run the up method
    private function runMigrationUp(string $migrationFilePath): void
    {
        // Get migration name and object
        $migrationFileName = basename($migrationFilePath);
        $migration = $this->loadMigration($migrationFilePath);

        echo "Running migration: {$migrationFileName}" . PHP_EOL;

        $migration->up();

        // Record successful migration so that it is skipped on next run
        $this->database->execute(
            "INSERT INTO migrations (migration_name) VALUES (:migration_name)",
            ["migration_name" => $migrationFileName],
        );

        echo "Migration complete: {$migrationFileName}" . PHP_EOL;
    }

    // Load migration class and run the down method
    private function runMigrationDown(string $migrationFileName): void
    {
        // Get migration name and object
        $migrationFilePath = __DIR__ . "/migrations/" . $migrationFileName;
        $migration = $this->loadMigration($migrationFilePath);

        echo "Rolling back migration: {$migrationFileName}" . PHP_EOL;

        $migration->down();

        // Record successful roll back so that migration will be included in next up() call
        $this->database->execute(
            "DELETE FROM migrations WHERE migration_name = :migration_name",
            ["migration_name" => $migrationFileName],
        );

        echo "Migration roll back complete: {$migrationFileName}" . PHP_EOL;
    }

    //Method to get the anonymous migration class from the file
    private function loadMigration(string $migrationFilePath): Migration
    {
        $getMigrationClass = require $migrationFilePath;
        return $getMigrationClass($this->database);
    }
}
