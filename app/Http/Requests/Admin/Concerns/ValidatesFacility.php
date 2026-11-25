<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Facility;
use App\Support\FacilityIcons;
use Closure;
use Illuminate\Validation\Rule;

trait ValidatesFacility
{
    /**
     * @return array<string, mixed>
     */
    protected function facilityRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule()],
            'scope' => ['required', 'string', Rule::in(['umum', 'kamar'])],
            'icon' => ['required', 'string', Rule::in(FacilityIcons::ALL)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama fasilitas wajib diisi.',
            'name.string' => 'Nama fasilitas harus berupa teks.',
            'name.max' => 'Nama fasilitas maksimal 100 karakter.',
            'scope.required' => 'Ruang lingkup wajib dipilih.',
            'scope.in' => 'Ruang lingkup harus umum atau kamar.',
            'icon.required' => 'Ikon wajib dipilih.',
            'icon.in' => 'Ikon tidak ada pada daftar ikon yang diizinkan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (! is_string($name)) {
            return;
        }

        $normalized = preg_replace('/\s+/', ' ', trim($name));

        if ($normalized !== null) {
            $this->merge(['name' => $normalized]);
        }
    }

    private function uniqueNameRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $query = Facility::query()
                ->whereRaw('lower(name) = lower(?)', [(string) $value]);

            $facility = $this->route('facility');

            if ($facility instanceof Facility) {
                $query->whereKeyNot($facility->getKey());
            }

            if ($query->exists()) {
                $fail('Nama fasilitas sudah dipakai.');
            }
        };
    }
}
