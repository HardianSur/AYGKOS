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
        Schema::create('room_type_rules', function (Blueprint $table) {
            $table->foreignUuid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('rule_id')->constrained()->restrictOnDelete();

            $table->primary(['room_type_id', 'rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_type_rules');
    }
};
