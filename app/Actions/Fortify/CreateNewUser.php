<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly OtpService $otpService) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['whatsapp_number'] = $this->normalizeWhatsappNumber($input['whatsapp_number'] ?? null);

        Validator::make($input, [
            ...$this->profileRules(),
            'role' => ['required', 'string', Rule::in(['customer', 'pemilik_kost'])],
            'whatsapp_number' => [
                'nullable',
                'required_if:role,pemilik_kost',
                'string',
                'regex:/^628[0-9]{7,11}$/',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ], [
            'whatsapp_number.required_if' => 'Nomor WhatsApp wajib diisi untuk peran pemilik kost.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp tidak valid. Gunakan nomor Indonesia, misalnya 081234567890.',
            'whatsapp_number.unique' => 'Nomor WhatsApp sudah dipakai akun lain.',
        ])->validate();

        $isOwner = $input['role'] === 'pemilik_kost';

        $user = User::create([
            'role_id' => Role::where('name', $input['role'])->value('id'),
            'name' => $input['name'],
            'email' => $input['email'],
            'whatsapp_number' => $isOwner ? $input['whatsapp_number'] : null,
            'password' => $input['password'],
        ]);

        if ($isOwner) {
            $this->otpService->issue($user, (string) $user->whatsapp_number);
        }

        return $user;
    }

    /**
     * Normalisasi nomor WhatsApp Indonesia ke awalan `62`.
     */
    private function normalizeWhatsappNumber(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $number);

        if ($digits === null || $digits === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }
}
