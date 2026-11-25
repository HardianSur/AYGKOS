<?php

namespace Database\Seeders;

use App\Models\Specification;
use Illuminate\Database\Seeder;

class SpecificationSeeder extends Seeder
{
    /**
     * Seed the room specifications master data.
     */
    public function run(): void
    {
        $specifications = [
            ['name' => 'Luas Kamar', 'input_type' => 'number', 'unit' => 'm2'],
            ['name' => 'Daya Listrik', 'input_type' => 'number', 'unit' => 'watt'],
            ['name' => 'Jumlah Penghuni', 'input_type' => 'number', 'unit' => 'orang'],
            ['name' => 'Kamar Mandi Dalam', 'input_type' => 'boolean', 'unit' => null],
            ['name' => 'Perabot', 'input_type' => 'text', 'unit' => null],
        ];

        foreach ($specifications as $specification) {
            Specification::firstOrCreate(['name' => $specification['name']], $specification);
        }
    }
}
