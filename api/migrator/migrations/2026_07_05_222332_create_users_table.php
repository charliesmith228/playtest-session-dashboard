<?php

declare(strict_types=1);

namespace App\Migrator\Migrations;

use App\Database;
use App\Migrator\Migration;

return function (Database $database)
{
    return new class($database) extends Migration
    {
        public function up(): void
        {
            $this->database->execute("
                CREATE TABLE users (
                    id SERIAL NOT NULL PRIMARY KEY,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    playtester_focus VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                )
            ");

            $this->database->execute("
                CREATE INDEX idx_users_email ON users(email)
            ");
        }

        public function down(): void
        {
            $this->database->execute("DROP TABLE IF EXISTS users");
        }
    };
};