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
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('scope', 10)->default('umum');
            $table->string('icon', 50)->default('building-2');
        });

        DB::statement('alter table facilities alter column scope drop default');
        DB::statement('alter table facilities alter column icon drop default');
        DB::statement("alter table facilities add constraint facilities_scope_values check (scope in ('umum', 'kamar'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('alter table facilities drop constraint facilities_scope_values');

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['scope', 'icon']);
        });
    }
};
