<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable()->unique();
            $table->string('telegram_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->enum('role', ['parent', 'nanny', 'admin', 'moderator']);
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->enum('language', ['ru', 'kk'])->default('ru');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_phone_format CHECK (phone IS NULL OR phone ~ '^\\+77[0-9]{9}$');");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
