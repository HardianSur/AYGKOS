<?php

use App\Models\Facility;
use App\Models\Kost;
use App\Models\Role;
use App\Models\RoomType;
use App\Models\User;
use App\Support\FacilityIcons;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $attributes
 */
function facilityUserWithRole(string $roleName, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role_id' => Role::where('name', $roleName)->value('id'),
    ], $attributes));
}

function createUsedFacility(string $name = 'WiFi'): Facility
{
    $owner = User::factory()->create();
    $kost = Kost::create([
        'owner_id' => $owner->id,
        'name' => 'Kost Melati',
        'address' => 'Jl. Contoh 1',
        'city' => 'Bandung',
        'latitude' => -6.9,
        'longitude' => 107.6,
        'type' => 'putra',
    ]);
    $roomType = RoomType::create([
        'kost_id' => $kost->id,
        'name' => 'Kamar AC',
        'price_per_month' => 900000,
        'total_rooms' => 5,
        'available_rooms' => 3,
    ]);

    $facility = Facility::create(['name' => $name, 'scope' => 'umum', 'icon' => 'wifi']);
    $kost->facilities()->attach($facility->id);
    $roomType->facilities()->attach($facility->id);

    return $facility;
}

test('customers and kost owners cannot manage facilities', function () {
    $customer = facilityUserWithRole('customer');
    $owner = facilityUserWithRole('pemilik_kost', [
        'whatsapp_number' => '6281234567890',
        'whatsapp_verified_at' => now(),
    ]);
    $facility = Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);

    foreach ([$customer, $owner] as $user) {
        $this->actingAs($user)->get(route('admin.facilities.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.facilities.store'), [
            'name' => 'Parkir',
            'scope' => 'umum',
            'icon' => 'car',
        ])->assertForbidden();
        $this->actingAs($user)->put(route('admin.facilities.update', $facility), [
            'name' => 'WiFi',
            'scope' => 'umum',
            'icon' => 'wifi',
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.facilities.destroy', $facility))->assertForbidden();
    }
});

test('an admin can add a facility and its name is normalized', function () {
    $admin = facilityUserWithRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.facilities.store'), [
            'name' => '  Ruang   Tamu  ',
            'scope' => 'umum',
            'icon' => 'sofa',
        ])
        ->assertRedirect(route('admin.facilities.index'));

    expect(Facility::where('name', 'Ruang Tamu')->exists())->toBeTrue();
});

test('an admin can change the name and icon of a facility', function () {
    $admin = facilityUserWithRole('admin');
    $facility = Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);

    $this->actingAs($admin)
        ->put(route('admin.facilities.update', $facility), [
            'name' => 'WiFi Cepat',
            'scope' => 'umum',
            'icon' => 'waves',
        ])
        ->assertRedirect(route('admin.facilities.index'));

    expect($facility->fresh())
        ->name->toBe('WiFi Cepat')
        ->icon->toBe('waves');
});

test('an admin can delete a facility without users', function () {
    $admin = facilityUserWithRole('admin');
    $facility = Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);

    $this->actingAs($admin)
        ->delete(route('admin.facilities.destroy', $facility))
        ->assertRedirect(route('admin.facilities.index'));

    expect(Facility::find($facility->id))->toBeNull();
});

test('deleting a used facility is rejected with its users', function () {
    $admin = facilityUserWithRole('admin');
    $facility = createUsedFacility();

    $this->actingAs($admin)
        ->from(route('admin.facilities.index'))
        ->delete(route('admin.facilities.destroy', $facility))
        ->assertRedirect(route('admin.facilities.index'))
        ->assertSessionHasErrors('facility');

    expect(Facility::find($facility->id))->not->toBeNull()
        ->and(session('errors')->first('facility'))
        ->toContain('Kost Melati')
        ->toContain('Kamar AC');
});

test('changing the scope of a used facility is rejected', function () {
    $admin = facilityUserWithRole('admin');
    $facility = createUsedFacility();

    $this->actingAs($admin)
        ->from(route('admin.facilities.index'))
        ->put(route('admin.facilities.update', $facility), [
            'name' => 'WiFi',
            'scope' => 'kamar',
            'icon' => 'wifi',
        ])
        ->assertSessionHasErrors('scope');

    expect($facility->fresh()->scope)->toBe('umum');
});

test('changing the scope of an unused facility is allowed', function () {
    $admin = facilityUserWithRole('admin');
    $facility = Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);

    $this->actingAs($admin)
        ->put(route('admin.facilities.update', $facility), [
            'name' => 'WiFi',
            'scope' => 'kamar',
            'icon' => 'wifi',
        ])
        ->assertRedirect(route('admin.facilities.index'));

    expect($facility->fresh()->scope)->toBe('kamar');
});

test('a name differing only by letter case is rejected', function () {
    $admin = facilityUserWithRole('admin');
    Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);

    $this->actingAs($admin)
        ->from(route('admin.facilities.index'))
        ->post(route('admin.facilities.store'), [
            'name' => 'wifi',
            'scope' => 'umum',
            'icon' => 'wifi',
        ])
        ->assertSessionHasErrors('name');
});

test('the facility list carries usage counts and allowed icons', function () {
    $admin = facilityUserWithRole('admin');
    createUsedFacility();
    Facility::create(['name' => 'A Bebas', 'scope' => 'kamar', 'icon' => 'bed']);

    $this->actingAs($admin)
        ->get(route('admin.facilities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/facilities/index')
            ->where('iconNames', FacilityIcons::names())
            ->has('facilities', 2)
            ->where('facilities.0.name', 'A Bebas')
            ->where('facilities.0.kost_count', 0)
            ->where('facilities.0.room_type_count', 0)
            ->where('facilities.1.name', 'WiFi')
            ->where('facilities.1.kost_count', 1)
            ->where('facilities.1.room_type_count', 1)
        );
});
