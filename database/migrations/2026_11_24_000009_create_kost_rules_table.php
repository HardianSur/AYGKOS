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
        Schema::create('kost_rules', function (Blueprint $table) {
            $table->foreignUuid('kost_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('rule_id')->constrained()->restrictOnDelete();

            $table->primary(['kost_id', 'rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kost_rules');
    }
};
