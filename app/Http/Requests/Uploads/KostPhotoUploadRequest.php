<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

class KostPhotoUploadRequest extends FormRequest
{
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        /** @var array{max_kb: int, mimes: array<int, string>} $limits */
        $limits = config('uploads.kost_photo');

        return [
            'photo' => [
                'required',
                'file',
                'mimes:'.implode(',', $limits['mimes']),
                'max:'.$limits['max_kb'],
            ],
        ];
    }
}
