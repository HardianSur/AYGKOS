<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Support\FacilityIcons;
use Illuminate\Database\Seeder;
use RuntimeException;

class FacilitySeeder extends Seeder
{
    /**
     * Seed the facilities master data.
     */
    public function run(): void
    {
        $facilities = [
            ['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi'],
            ['name' => 'Parkir', 'scope' => 'umum', 'icon' => 'car'],
            ['name' => 'Keamanan 24 Jam', 'scope' => 'umum', 'icon' => 'shield-check'],
            ['name' => 'Dapur Bersama', 'scope' => 'umum', 'icon' => 'utensils'],
            ['name' => 'Ruang Tamu', 'scope' => 'umum', 'icon' => 'sofa'],
            ['name' => 'Laundry', 'scope' => 'umum', 'icon' => 'washing-machine'],
            ['name' => 'CCTV', 'scope' => 'umum', 'icon' => 'video'],
            ['name' => 'AC', 'scope' => 'kamar', 'icon' => 'snowflake'],
            ['name' => 'Kamar Mandi Dalam', 'scope' => 'kamar', 'icon' => 'bath'],
            ['name' => 'Kasur', 'scope' => 'kamar', 'icon' => 'bed'],
            ['name' => 'TV', 'scope' => 'kamar', 'icon' => 'tv'],
            ['name' => 'Meja Belajar', 'scope' => 'kamar', 'icon' => 'table'],
            ['name' => 'Lemari Pakaian', 'scope' => 'kamar', 'icon' => 'shirt'],
            ['name' => 'Kipas Angin', 'scope' => 'kamar', 'icon' => 'fan'],
            ['name' => 'Kulkas', 'scope' => 'kamar', 'icon' => 'refrigerator'],
        ];

        foreach ($facilities as $facility) {
            if (! FacilityIcons::isAllowed($facility['icon'])) {
                throw new RuntimeException("Ikon fasilitas tidak dikenal: {$facility['icon']}");
            }

            Facility::firstOrCreate(['name' => $facility['name']], $facility);
        }
    }
}
