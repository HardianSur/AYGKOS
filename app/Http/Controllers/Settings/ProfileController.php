<?php

namespace App\Http\Controllers\Settings;

use App\Actions\StoreAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, StoreAvatar $storeAvatar): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('avatar'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->hasFile('avatar')) {
            $oldPath = $user->avatar_path;

            try {
                /** @var UploadedFile $file */
                $file = $request->file('avatar');
                $user->forceFill(['avatar_path' => $storeAvatar->handle($file)])->save();

                if ($oldPath !== null) {
                    Storage::disk('public')->delete($oldPath);
                }
            } catch (Throwable $e) {
                report($e);

                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => __('Your profile photo could not be saved.'),
                ]);

                return to_route('profile.edit');
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
