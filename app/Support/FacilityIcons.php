<?php

namespace App\Support;

/**
 * Daftar nama ikon lucide yang boleh dipakai master fasilitas.
 *
 * Nama disimpan di basis data; frontend memetakannya lewat `icon.tsx`.
 */
final class FacilityIcons
{
    /**
     * @var array<int, string>
     */
    public const ALL = [
        'building-2',
        'wifi',
        'car',
        'shield-check',
        'utensils',
        'sofa',
        'washing-machine',
        'video',
        'snowflake',
        'bath',
        'bed',
        'tv',
        'lamp',
        'fan',
        'refrigerator',
        'air-vent',
        'shirt',
        'table',
        'armchair',
        'clock',
        'waves',
    ];

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return self::ALL;
    }

    public static function isAllowed(string $name): bool
    {
        return in_array($name, self::ALL, true);
    }
}
