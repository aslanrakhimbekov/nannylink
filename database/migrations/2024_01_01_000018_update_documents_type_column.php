<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop Postgres CHECK constraint on documents.type column if exists
        try {
            DB::statement("ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_type_check");
        } catch (\Throwable $e) {
            // Ignore if constraint does not exist
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->string('type', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('type', 255)->change();
        });
    }
};
