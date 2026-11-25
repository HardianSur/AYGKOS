<?php

namespace App\Actions\MasterData;

use App\Models\Facility;
use Illuminate\Validation\ValidationException;

class ManageFacility
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Facility
    {
        return Facility::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Facility $facility, array $attributes): Facility
    {
        if ($attributes['scope'] !== $facility->scope && $this->isUsed($facility)) {
            throw ValidationException::withMessages([
                'scope' => "Ruang lingkup fasilitas \"{$facility->name}\" tidak dapat diubah karena masih dipakai oleh {$this->usage($facility)}.",
            ]);
        }

        $facility->update($attributes);

        return $facility;
    }

    public function delete(Facility $facility): void
    {
        if ($this->isUsed($facility)) {
            throw ValidationException::withMessages([
                'facility' => "Fasilitas \"{$facility->name}\" tidak dapat dihapus karena masih dipakai oleh {$this->usage($facility)}.",
            ]);
        }

        $facility->delete();
    }

    private function isUsed(Facility $facility): bool
    {
        return $facility->kosts()->exists() || $facility->roomTypes()->exists();
    }

    private function usage(Facility $facility): string
    {
        $kosts = $facility->kosts()->orderBy('name')->pluck('name')
            ->map(fn (mixed $name): string => (string) $name)
            ->implode(', ');
        $roomTypes = $facility->roomTypes()->orderBy('name')->pluck('name')
            ->map(fn (mixed $name): string => (string) $name)
            ->implode(', ');

        $parts = [];

        if ($kosts !== '') {
            $parts[] = "kost {$kosts}";
        }

        if ($roomTypes !== '') {
            $parts[] = "tipe kamar {$roomTypes}";
        }

        return implode(' dan ', $parts);
    }
}
