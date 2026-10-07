<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foydalanuvchi tanlagan baholash sahifasi ko'rinishi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('valuation_design')->nullable()->after('telegram_username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('valuation_design');
        });
    }
};
