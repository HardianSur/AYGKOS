<?php

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Facility;
use App\Models\Kost;
use App\Models\KostPhoto;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @return array<string, mixed>
 */
function kostAttributes(User $owner): array
{
    return [
        'owner_id' => $owner->id,
        'name' => 'Kost Melati',
        'address' => 'Jl. Contoh 1',
        'city' => 'Bandung',
        'latitude' => -6.9,
        'longitude' => 107.6,
        'type' => 'putra',
        'verification_document_path' => 'kosts/ktp.pdf',
    ];
}

it('resolves the role of a factory user to customer', function () {
    expect(User::factory()->create()->role->name)->toBe('customer');
});

it('writes no timestamp columns for facilities and kost_photos', function () {
    $owner = User::factory()->create();
    $kost = Kost::create(kostAttributes($owner));

    $facility = Facility::create(['name' => 'WiFi']);
    $photo = KostPhoto::create(['kost_id' => $kost->id, 'photo_path' => 'kosts/1.jpg']);

    expect($facility->getAttributes())->not->toHaveKey('created_at')
        ->and($photo->getAttributes())->not->toHaveKey('created_at')
        ->and($facility->exists)->toBeTrue()
        ->and($photo->exists)->toBeTrue();
});

it('hides a soft deleted kost from default queries only', function () {
    $owner = User::factory()->create();
    $kost = Kost::create(kostAttributes($owner));

    $kost->delete();

    expect(Kost::all())->toHaveCount(0)
        ->and(Kost::withTrashed()->count())->toBe(1)
        ->and($kost->fresh()?->deleted_at)->not->toBeNull();
});

it('deletes complaint attachments with their complaint', function () {
    $owner = User::factory()->create();
    $complaint = Complaint::create([
        'user_id' => $owner->id,
        'category' => 'kendala_sistem',
        'title' => 'Tidak bisa unggah',
        'description' => 'Galat unggah berkas.',
    ]);

    ComplaintAttachment::create(['complaint_id' => $complaint->id, 'file_path' => 'complaints/a.pdf']);

    $complaint->delete();

    expect(DB::table('complaint_attachments')->count())->toBe(0);
});

it('rejects a duplicate kost_facilities pair and accepts different facilities', function () {
    $owner = User::factory()->create();
    $kost = Kost::create(kostAttributes($owner));
    $first = Facility::create(['name' => 'AC']);
    $second = Facility::create(['name' => 'Parkir']);

    $kost->facilities()->attach($first->id);

    // savepoint: Postgres aborts the whole transaction after a constraint violation
    expect(fn () => DB::transaction(fn () => $kost->facilities()->attach($first->id)))
        ->toThrow(QueryException::class);

    $kost->facilities()->attach($second->id);

    expect($kost->facilities()->count())->toBe(2);
});

it('allows the same room number on different kosts', function () {
    $owner = User::factory()->create();
    $first = Kost::create(kostAttributes($owner));
    $second = Kost::create(kostAttributes($owner) + ['name' => 'Kost Anggrek']);

    Room::create(['kost_id' => $first->id, 'room_number' => 'A1', 'price_per_month' => 900000]);
    Room::create(['kost_id' => $second->id, 'room_number' => 'A1', 'price_per_month' => 900000]);

    expect(Room::count())->toBe(2);
});

it('fills the id through HasUuids and lets multiple users omit whatsapp_number', function () {
    $role = Role::firstOrCreate(['name' => 'customer']);

    foreach ([1, 2] as $ignored) {
        DB::table('users')->insert([
            'id' => (string) Str::uuid7(),
            'role_id' => $role->id,
            'name' => 'Pengguna',
            'email' => Str::uuid7().'@example.com',
            'password' => 'x',
        ]);
    }

    expect(DB::table('users')->whereNull('whatsapp_number')->count())->toBe(2)
        ->and(Schema::hasColumn('users', 'whatsapp_number'))->toBeTrue();
});
