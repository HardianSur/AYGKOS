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
    }
};
