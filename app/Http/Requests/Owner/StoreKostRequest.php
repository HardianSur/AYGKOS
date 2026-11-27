<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKostRequest extends FormRequest
{
    /**
     * The documents a submission may attach beyond the mandatory ones.
     *
     * @var array<int, string>
     */
    private const OPTIONAL_DOCUMENTS = [
        'domisili_usaha',
        'izin_lingkungan',
        'izin_kelurahan',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var array{max_kb: int, mimes: array<int, string>} $photoLimits */
        $photoLimits = config('uploads.kost_photo');

        /** @var array{max_kb: int, mimes: array<int, string>} $documentLimits */
        $documentLimits = config('uploads.kost_document');

        /** @var array<int, string> $requiredDocuments */
        $requiredDocuments = config('uploads.kost_documents_wajib');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-11,6.1'],
            'longitude' => ['required', 'numeric', 'between:94.9,141.1'],
            'type' => ['required', 'string', 'in:putra,putri,campur'],
            'description' => ['nullable', 'string', 'max:2000'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => [
                'uuid',
                'distinct',
                Rule::exists('facilities', 'id')->where('scope', 'umum'),
            ],
            'rules' => ['nullable', 'array'],
            'rules.*' => [
                'uuid',
                'distinct',
                Rule::exists('rules', 'id')->where('scope', 'kost'),
            ],
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => [
                'required',
                'file',
                'mimes:'.implode(',', $photoLimits['mimes']),
                'max:'.$photoLimits['max_kb'],
            ],
            'documents' => ['required', 'array'],
        ];

        $documentTypes = array_values(array_unique([
            ...$requiredDocuments,
            ...self::OPTIONAL_DOCUMENTS,
        ]));

        foreach ($documentTypes as $type) {
            $rules["documents.$type"] = [
                in_array($type, $requiredDocuments, true) ? 'required' : 'nullable',
                'file',
                'mimes:'.implode(',', $documentLimits['mimes']),
                'max:'.$documentLimits['max_kb'],
            ];
        }

        return $rules;
    }
}
