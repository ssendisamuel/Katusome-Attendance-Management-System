<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50); // admin, principal, registrar, etc.
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique role per user per context
            $table->unique(['user_id', 'role', 'campus_id', 'faculty_id', 'department_id'], 'user_role_context_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
