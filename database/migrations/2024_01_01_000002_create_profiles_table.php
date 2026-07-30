<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('avatar_url')->nullable();
            $table->string('video_url')->nullable();
            $table->text('bio')->nullable();
            $table->integer('hourly_rate')->nullable();
            $table->integer('experience_years')->nullable();
            $table->integer('balance_coins')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE profiles ADD COLUMN location geography(Point, 4326) NULL;');
        DB::statement('CREATE INDEX profiles_location_gist ON profiles USING gist (location);');
        DB::statement('ALTER TABLE profiles ADD CONSTRAINT chk_balance_coins CHECK (balance_coins >= 0);');
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
