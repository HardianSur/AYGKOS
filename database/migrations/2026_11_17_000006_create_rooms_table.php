<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->string('room_number', 50);
            $table->integer('price_per_month');
            $table->string('status', 20)->default('tersedia');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kost_id', 'room_number']);
            $table->index(['kost_id', 'status']);
        });

        DB::statement('alter table rooms add constraint rooms_price_positive check (price_per_month > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
