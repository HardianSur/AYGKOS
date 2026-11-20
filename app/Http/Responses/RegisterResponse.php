<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arahkan pendaftar ke verifikasi yang sesuai dengan perannya.
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        return redirect()->route(
            $user instanceof User && $user->isPemilikKost()
                ? 'verification.whatsapp.notice'
                : 'verification.notice',
        );
    }
}
