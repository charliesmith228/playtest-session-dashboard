<?php

declare(strict_types=1);

namespace App\Migrator\Migrations;

use App\Database;
use App\Migrator\Migration;

return static function (Database $database) {
    return new class ($database) extends Migration {
        public function up(): void
        {
            $this->database->execute("
                CREATE TABLE video_games (
                    id SERIAL NOT NULL PRIMARY KEY,
                    game_name VARCHAR(255) NOT NULL,
                    platforms VARCHAR(100) NOT NULL,
                    build VARCHAR(100) NOT NULL,
                    created_by SERIAL NOT NULL REFERENCES users(ID) ON DELETE CASCADE,
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                )
            ");
        }

        public function down(): void
        {
            $this->database->execute("DROP TABLE IF EXISTS video_games");
        }
    };
};
