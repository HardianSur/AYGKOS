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
        Schema::create('photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('room_type_id')->nullable()->constrained('room_types')->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('photo_path', 500);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
        });

        DB::statement(
            "alter table photos add constraint photos_category_room_type check (
                (category in ('bangunan', 'fasilitas_umum') and room_type_id is null)
                or (category in ('kamar', 'kamar_mandi') and room_type_id is not null)
            )"
        );
        DB::statement('create unique index photos_one_primary_kost on photos (kost_id) where is_primary and room_type_id is null');
        DB::statement('create unique index photos_one_primary_room_type on photos (room_type_id) where is_primary and room_type_id is not null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
