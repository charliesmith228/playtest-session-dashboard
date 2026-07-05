<?php

declare(strict_types=1);

namespace App\Migrator;

use App\Database;

abstract class Migration
{
    public function __construct(protected Database $database) {}

    // Method to apply the database migration
    abstract public function up(): void;

    // Method to reverse the database migration
    abstract public function down(): void;
}