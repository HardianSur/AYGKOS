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
        Schema::dropIfExists('kost_photos');
        Schema::dropIfExists('rooms');

        Schema::table('kosts', function (Blueprint $table) {
            $table->dropColumn(['rules', 'verification_document_path']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->string('scope', 10)->default('umum');
            $table->string('icon', 50)->default('building-2');
        });

        DB::statement('alter table facilities alter column scope drop default');
        DB::statement('alter table facilities alter column icon drop default');
        DB::statement("alter table facilities add constraint facilities_scope_values check (scope in ('umum', 'kamar'))");

        Schema::table('kost_facilities', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->foreign('facility_id')->references('id')->on('facilities')->restrictOnDelete();
        });

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

        Schema::create('rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('scope', 10);
        });

        DB::statement("alter table rules add constraint rules_scope_values check (scope in ('kost', 'kamar'))");

        Schema::create('specifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('input_type', 20);
            $table->string('unit', 20)->nullable();
        });

        DB::statement("alter table specifications add constraint specifications_input_type_values check (input_type in ('boolean', 'number', 'text'))");

        Schema::create('kost_rules', function (Blueprint $table) {
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('rule_id')->constrained()->restrictOnDelete();

            $table->primary(['kost_id', 'rule_id']);
        });

        Schema::create('room_type_facilities', function (Blueprint $table) {
            $table->foreignUuid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained()->restrictOnDelete();

            $table->primary(['room_type_id', 'facility_id']);
        });

        Schema::create('room_type_rules', function (Blueprint $table) {
            $table->foreignUuid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('rule_id')->constrained()->restrictOnDelete();

            $table->primary(['room_type_id', 'rule_id']);
        });

        Schema::create('room_type_specifications', function (Blueprint $table) {
            $table->foreignUuid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('specification_id')->constrained()->restrictOnDelete();
            $table->string('value', 100);

            $table->primary(['room_type_id', 'specification_id']);
        });

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

        Schema::create('kost_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('file_path', 500);

            $table->unique(['kost_id', 'type']);
        });

        DB::statement(
            "alter table kost_documents add constraint kost_documents_type_values check (
                type in ('domisili_usaha', 'izin_lingkungan', 'izin_kelurahan', 'nib', 'surat_pernyataan')
            )"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kost_documents');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('room_type_specifications');
        Schema::dropIfExists('room_type_rules');
        Schema::dropIfExists('room_type_facilities');
        Schema::dropIfExists('kost_rules');
        Schema::dropIfExists('specifications');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('room_types');

        Schema::table('kost_facilities', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
        });

        DB::statement('alter table facilities drop constraint facilities_scope_values');

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['scope', 'icon']);
        });

        Schema::table('kosts', function (Blueprint $table) {
            $table->text('rules')->nullable();
            $table->string('verification_document_path', 500)->default('');
        });

        DB::statement('alter table kosts alter column verification_document_path drop default');

        Schema::create('kost_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path', 500);
            $table->boolean('is_primary')->default(false);
        });

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
};
