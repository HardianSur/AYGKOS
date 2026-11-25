<?php

namespace Database\Seeders;

use App\Models\Rule;
use Illuminate\Database\Seeder;

class RuleSeeder extends Seeder
{
    /**
     * Seed the rules master data.
     */
    public function run(): void
    {
        $rules = [
            ['name' => 'Tidak boleh membawa hewan peliharaan', 'scope' => 'kost'],
            ['name' => 'Dilarang merokok di dalam kamar', 'scope' => 'kost'],
            ['name' => 'Jam tamu maksimal 22.00', 'scope' => 'kost'],
            ['name' => 'Pembayaran sewa paling lambat tanggal 5', 'scope' => 'kost'],
            ['name' => 'Hanya untuk satu orang', 'scope' => 'kamar'],
            ['name' => 'Dilarang memasak di kamar', 'scope' => 'kamar'],
            ['name' => 'Tamu dilarang menginap', 'scope' => 'kamar'],
        ];

        foreach ($rules as $rule) {
            Rule::firstOrCreate(['name' => $rule['name']], $rule);
        }
    }
}
