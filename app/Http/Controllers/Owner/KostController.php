<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Kost\SubmitKost;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreKostRequest;
use App\Models\Facility;
use App\Models\Kost;
use App\Models\Photo;
use App\Models\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class KostController extends Controller
{
    /**
     * Show the kost list owned by the signed-in owner.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Kost::class);

        $kosts = $request->user()
            ->kosts()
            ->with('photos')
            ->latest()
            ->paginate(12)
            ->through(fn (Kost $kost): array => [
                'id' => $kost->id,
                'name' => $kost->name,
                'city' => $kost->city,
                'type' => $kost->type,
                'status' => $kost->status,
                'status_reason' => $kost->status_reason,
                'photo_url' => $this->primaryPhotoUrl($kost),
            ]);

        return Inertia::render('owner/kosts/index', [
            'kosts' => $kosts,
        ]);
    }

    /**
     * Show the kost submission form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Kost::class);

        $facilities = Facility::query()
            ->where('scope', 'umum')
            ->orderBy('name')
            ->get()
            ->map(fn (Facility $facility): array => [
                'id' => $facility->id,
                'name' => $facility->name,
                'icon' => $facility->icon,
            ])
            ->values();

        $rules = Rule::query()
            ->where('scope', 'kost')
            ->orderBy('name')
            ->get()
            ->map(fn (Rule $rule): array => [
                'id' => $rule->id,
                'name' => $rule->name,
            ])
            ->values();

        return Inertia::render('owner/kosts/create', [
            'facilities' => $facilities,
            'rules' => $rules,
            'geoapifyKey' => config('services.geoapify.key'),
        ]);
    }

    /**
     * Store a new kost submission.
     */
    public function store(StoreKostRequest $request, SubmitKost $submitKost): RedirectResponse
    {
        Gate::authorize('create', Kost::class);

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $submitKost->handle($user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kost berhasil diajukan dan menunggu verifikasi.',
        ]);

        return to_route('owner.kosts.index');
    }

    /**
     * Get the public URL of the kost's primary photo, when one exists.
     */
    private function primaryPhotoUrl(Kost $kost): ?string
    {
        $photo = $kost->photos->firstWhere('is_primary', true);

        if (! $photo instanceof Photo) {
            return null;
        }

        return Storage::disk('public')->url($photo->photo_path);
    }
}
