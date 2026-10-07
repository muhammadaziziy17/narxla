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
        Schema::create('valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand');
            $table->string('model');
            $table->unsignedInteger('storage');
            $table->unsignedTinyInteger('battery');
            $table->string('condition');
            $table->unsignedInteger('price_low');
            $table->unsignedInteger('price_high');
            $table->unsignedTinyInteger('confidence');
            $table->text('insight');
            $table->unsignedTinyInteger('photos_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valuations');
    }
};
