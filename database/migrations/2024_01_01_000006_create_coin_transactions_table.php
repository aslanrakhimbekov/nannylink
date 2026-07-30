<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->index()->constrained('bookings')->cascadeOnDelete();
            $table->enum('type', ['deposit', 'spend', 'refund']);
            $table->integer('amount');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE coin_transactions ADD CONSTRAINT chk_amount CHECK (amount >= 0);');
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
