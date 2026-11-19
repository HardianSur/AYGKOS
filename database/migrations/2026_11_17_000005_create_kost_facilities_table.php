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
        Schema::create('kost_facilities', function (Blueprint $table) {
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('facility_id')->constrained()->cascadeOnDelete();

            $table->primary(['kost_id', 'facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kost_facilities');
    }
};
