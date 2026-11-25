<?php

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Facility;
use App\Models\Kost;
use App\Models\KostDocument;
use App\Models\Photo;
use App\Models\Role;
use App\Models\RoomType;
use App\Models\Rule;
use App\Models\Specification;
use App\Models\User;
use App\Support\FacilityIcons;
use Database\Seeders\FacilitySeeder;
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
    ];
}

/**
 * @return array<string, mixed>
 */
function roomTypeAttributes(string $kostId): array
{
    return [
        'kost_id' => $kostId,
        'name' => 'AC',
        'price_per_month' => 900000,
        'total_rooms' => 5,
        'available_rooms' => 3,
    ];
}

it('resolves the role of a factory user to customer', function () {
    expect(User::factory()->create()->role->name)->toBe('customer');
});

it('maps the catalog models to the documented tables', function () {
    expect((new RoomType)->getTable())->toBe('room_types')
        ->and((new Photo)->getTable())->toBe('photos')
        ->and((new KostDocument)->getTable())->toBe('kost_documents')
        ->and((new Rule)->getTable())->toBe('rules')
        ->and((new Specification)->getTable())->toBe('specifications');
});

it('writes no timestamp columns for master data and photos', function () {
    $owner = User::factory()->create();
    $kost = Kost::create(kostAttributes($owner));
    $roomType = RoomType::create(roomTypeAttributes($kost->id));

    $facility = Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);
    $rule = Rule::create(['name' => 'Dilarang merokok', 'scope' => 'kost']);
    $specification = Specification::create(['name' => 'Luas Kamar', 'input_type' => 'number', 'unit' => 'm2']);
    $photo = Photo::create(['kost_id' => $kost->id, 'category' => 'bangunan', 'photo_path' => 'kosts/1.jpg']);
    $document = KostDocument::create(['kost_id' => $kost->id, 'type' => 'nib', 'file_path' => 'kosts/nib.pdf']);

    expect($facility->getAttributes())->not->toHaveKey('created_at')
        ->and($rule->getAttributes())->not->toHaveKey('created_at')
        ->and($specification->getAttributes())->not->toHaveKey('created_at')
        ->and($photo->getAttributes())->not->toHaveKey('created_at')
        ->and($document->getAttributes())->not->toHaveKey('created_at')
        ->and($roomType->getAttributes())->toHaveKey('created_at');
});

it('hides a soft deleted kost and room type from default queries only', function () {
    $owner = User::factory()->create();
    $kost = Kost::create(kostAttributes($owner));
    $roomType = RoomType::create(roomTypeAttributes($kost->id));

    $kost->delete();
    $roomType->delete();

    expect(Kost::all())->toHaveCount(0)
        ->and(Kost::withTrashed()->count())->toBe(1)
        ->and(RoomType::all())->toHaveCount(0)
        ->and(RoomType::withTrashed()->count())->toBe(1)
        ->and($roomType->fresh()?->deleted_at)->not->toBeNull();
});

it('deletes the catalog children when a kost is force deleted', function () {
    $owner = User::factory()->create();
    $kost = Kost::create(kostAttributes($owner));
    $roomType = RoomType::create(roomTypeAttributes($kost->id));

    Photo::create(['kost_id' => $kost->id, 'category' => 'bangunan', 'photo_path' => 'kosts/1.jpg']);
    Photo::create([
        'kost_id' => $kost->id,
        'room_type_id' => $roomType->id,
        'category' => 'kamar',
        'photo_path' => 'kosts/2.jpg',
    ]);
    KostDocument::create(['kost_id' => $kost->id, 'type' => 'nib', 'file_path' => 'kosts/nib.pdf']);
    $facility = Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);
    $rule = Rule::create(['name' => 'Dilarang merokok', 'scope' => 'kost']);
    $kost->facilities()->attach($facility->id);
    $kost->kostRules()->attach($rule->id);

    $kost->forceDelete();

    expect(DB::table('room_types')->count())->toBe(0)
        ->and(DB::table('photos')->count())->toBe(0)
        ->and(DB::table('kost_documents')->count())->toBe(0)
        ->and(DB::table('kost_facilities')->count())->toBe(0)
        ->and(DB::table('kost_rules')->count())->toBe(0)
        ->and(Facility::count())->toBe(1)
        ->and(Rule::count())->toBe(1);
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
    $first = Facility::create(['name' => 'AC', 'scope' => 'kamar', 'icon' => 'snowflake']);
    $second = Facility::create(['name' => 'Parkir', 'scope' => 'umum', 'icon' => 'car']);

    $kost->facilities()->attach($first->id);

    // savepoint: Postgres aborts the whole transaction after a constraint violation
    expect(fn () => DB::transaction(fn () => $kost->facilities()->attach($first->id)))
        ->toThrow(QueryException::class);

    $kost->facilities()->attach($second->id);

    expect($kost->facilities()->count())->toBe(2);
});

it('allows the same room type name on different kosts', function () {
    $owner = User::factory()->create();
    $first = Kost::create(kostAttributes($owner));
    $second = Kost::create(kostAttributes($owner) + ['name' => 'Kost Anggrek']);

    RoomType::create(roomTypeAttributes($first->id));
    RoomType::create(roomTypeAttributes($second->id));

    expect(RoomType::count())->toBe(2);
});

it('only accepts facility icons from the shared list', function () {
    expect(FacilityIcons::isAllowed('wifi'))->toBeTrue()
        ->and(FacilityIcons::isAllowed('bogus-icon'))->toBeFalse();

    $this->seed(FacilitySeeder::class);

    foreach (Facility::all() as $facility) {
        expect(FacilityIcons::isAllowed($facility->icon))->toBeTrue();
    }
});

it('menolak nama fasilitas yang hanya berbeda huruf besar', function () {
    Facility::create(['name' => 'WiFi', 'scope' => 'umum', 'icon' => 'wifi']);

    expect(fn () => DB::transaction(fn () => Facility::create([
        'name' => 'wifi',
        'scope' => 'umum',
        'icon' => 'wifi',
    ])))->toThrow(QueryException::class);
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
