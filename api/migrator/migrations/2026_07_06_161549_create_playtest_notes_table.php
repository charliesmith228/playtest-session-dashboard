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
                CREATE TABLE playtest_notes (
                    id SERIAL NOT NULL PRIMARY KEY,
                    playtest_id SERIAL NOT NULL REFERENCES playtests(ID) ON DELETE CASCADE,
                    notes TEXT NOT NULL,
                    created_by SERIAL NOT NULL REFERENCES users(ID) ON DELETE CASCADE,
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                )
            ");
        }

        public function down(): void
        {
            $this->database->execute("DROP TABLE IF EXISTS playtest_notes");
        }
    };
};