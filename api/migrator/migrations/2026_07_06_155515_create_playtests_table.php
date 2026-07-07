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
                CREATE TABLE playtests (
                    ID SERIAL PRIMARY KEY,
                    game_name VARCHAR(255) NOT NULL,
                    playtest_description TEXT,
                    start_time TIMESTAMP NOT NULL,
                    end_time TIMESTAMP NOT NULL,
                    created_by SERIAL REFERENCES users (ID),
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                )
            ");
        }

        public function down(): void
        {
            $this->database->execute("DROP TABLE IF EXISTS playtests");
        }
    };
};