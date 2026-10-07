<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('valuations', function (Blueprint $table) {
            // Jonli bozor tahlilida narxlar so'mda saqlanadi (katalogda — dollar).
            $table->unsignedBigInteger('price_low_uzs')->nullable()->after('price_high');
            $table->unsignedBigInteger('price_high_uzs')->nullable()->after('price_low_uzs');

            // 'market' — jonli e'lonlar tahlili, 'catalog' — taxminiy katalog narxi.
            $table->string('price_source')->default('catalog')->after('price_high_uzs');

            // Topilgan e'lonlar: [{title, url, price, currency}].
            $table->json('sources')->nullable()->after('price_source');

            $table->timestamp('checked_at')->nullable()->after('sources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valuations', function (Blueprint $table) {
            $table->dropColumn(['price_low_uzs', 'price_high_uzs', 'price_source', 'sources', 'checked_at']);
        });
    }
};
