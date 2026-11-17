<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintAttachmentUploadRequest extends FormRequest
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
        /** @var array{max_kb: int, max_files: int, mimes: array<int, string>} $limits */
        $limits = config('uploads.complaint_attachment');

        return [
            'attachments' => [
                'required',
                'array',
                'max:'.$limits['max_files'],
            ],
            'attachments.*' => [
                'file',
                'mimes:'.implode(',', $limits['mimes']),
                'max:'.$limits['max_kb'],
            ],
        ];
    }
}
