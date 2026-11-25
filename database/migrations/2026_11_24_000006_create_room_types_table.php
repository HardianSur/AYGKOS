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
        Schema::create('room_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('price_per_month');
            $table->integer('total_rooms');
            $table->integer('available_rooms')->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('nonaktif');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kost_id', 'name']);
            $table->index(['kost_id', 'status']);
        });

        DB::statement('alter table room_types add constraint room_types_price_positive check (price_per_month > 0)');
        DB::statement('alter table room_types add constraint room_types_total_positive check (total_rooms > 0)');
        DB::statement('alter table room_types add constraint room_types_available_range check (available_rooms between 0 and total_rooms)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
