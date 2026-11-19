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
        Schema::create('kosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('address', 500);
            $table->string('city', 100);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('type', 20);
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->string('status', 30)->default('menunggu_verifikasi');
            $table->text('status_reason')->nullable();
            $table->string('verification_document_path', 500);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['latitude', 'longitude']);
            $table->index('status');
        });

        DB::statement('alter table kosts add constraint kosts_latitude_range check (latitude between -90 and 90)');
        DB::statement('alter table kosts add constraint kosts_longitude_range check (longitude between -180 and 180)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kosts');
    }
};
