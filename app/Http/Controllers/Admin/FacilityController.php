<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MasterData\ManageFacility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFacilityRequest;
use App\Http\Requests\Admin\UpdateFacilityRequest;
use App\Models\Facility;
use App\Support\FacilityIcons;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(): Response
    {
        $facilities = Facility::query()
            ->withCount(['kosts', 'roomTypes'])
            ->orderBy('name')
            ->get()
            ->map(fn (Facility $facility): array => [
                'id' => $facility->id,
                'name' => $facility->name,
                'scope' => $facility->scope,
                'icon' => $facility->icon,
                'kost_count' => (int) $facility->getAttribute('kosts_count'),
                'room_type_count' => (int) $facility->getAttribute('room_types_count'),
            ]);

        return Inertia::render('admin/facilities/index', [
            'facilities' => $facilities,
            'iconNames' => FacilityIcons::names(),
        ]);
    }

    public function store(StoreFacilityRequest $request, ManageFacility $action): RedirectResponse
    {
        $action->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fasilitas berhasil ditambahkan.']);

        return to_route('admin.facilities.index');
    }

    public function update(UpdateFacilityRequest $request, Facility $facility, ManageFacility $action): RedirectResponse
    {
        $action->update($facility, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fasilitas berhasil diperbarui.']);

        return to_route('admin.facilities.index');
    }

    public function destroy(Facility $facility, ManageFacility $action): RedirectResponse
    {
        $action->delete($facility);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fasilitas berhasil dihapus.']);

        return to_route('admin.facilities.index');
    }
}
