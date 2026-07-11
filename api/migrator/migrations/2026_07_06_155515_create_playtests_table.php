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
                CREATE TABLE playtests (
                    id SERIAL NOT NULL PRIMARY KEY,
                    video_game_id SERIAL NOT NULL REFERENCES video_games(ID) ON DELETE CASCADE,
                    playtest_description TEXT,
                    start_time TIMESTAMP NOT NULL,
                    end_time TIMESTAMP NOT NULL,
                    created_by SERIAL NOT NULL REFERENCES users(ID) ON DELETE CASCADE,
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
