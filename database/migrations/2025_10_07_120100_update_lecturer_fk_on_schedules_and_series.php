<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // No-op: foreign key behavior already handled in previous migration.
    }

    public function down(): void
    {
        // No-op
    }
};