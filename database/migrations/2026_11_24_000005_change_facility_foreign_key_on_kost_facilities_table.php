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
        Schema::table('kost_facilities', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->foreign('facility_id')->references('id')->on('facilities')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kost_facilities', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
        });
    }
};
