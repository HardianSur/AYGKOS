<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('alter table facilities drop constraint facilities_name_unique');
        DB::statement('create unique index facilities_name_lower_unique on facilities (lower(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop index facilities_name_lower_unique');
        DB::statement('alter table facilities add constraint facilities_name_unique unique (name)');
    }
};
