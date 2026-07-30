<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->json('languages')->nullable()->after('bio');
            $table->json('skills')->nullable()->after('languages');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->json('compliments')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['languages', 'skills']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('compliments');
        });
    }
};
