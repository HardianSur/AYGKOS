<?php

use App\Actions\StoreImage;
use App\Models\Facility;
use App\Models\Kost;
use App\Models\Photo;
use App\Models\Role;
use App\Models\Rule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;

/**
 * @param  array<string, mixed>  $attributes
 */
function makeKostUser(string $roleName, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role_id' => Role::where('name', $roleName)->value('id'),
    ], $attributes));
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeKostOwner(array $attributes = []): User
{
    return makeKostUser('pemilik_kost', array_merge([
        'whatsapp_number' => fake()->unique()->numerify('628##########'),
        'whatsapp_verified_at' => now(),
    ], $attributes));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function kostPayload(Facility $facility, Rule $rule, array $overrides = []): array
{
    return array_merge([
        'name' => 'Kost Melati',
        'address' => 'Jl. Melati No. 1, RT 02/RW 03',
        'city' => 'Kota Bandung',
        'latitude' => '-6.9000000',
        'longitude' => '107.6000000',
        'type' => 'putra',
        'description' => 'Kost nyaman dekat kampus.',
        'facilities' => [$facility->id],
        'rules' => [$rule->id],
        'photos' => [
            UploadedFile::fake()->image('depan.jpg', 1200, 800),
        ],
        'documents' => [
            'nib' => UploadedFile::fake()->create('nib.pdf', 100, 'application/pdf'),
            'surat_pernyataan' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ],
    ], $overrides);
}

function umumFacility(string $name = 'WiFi'): Facility
{
    return Facility::create(['name' => $name, 'scope' => 'umum', 'icon' => 'wifi']);
}

function kostRule(string $name = 'Dilarang merokok'): Rule
{
    return Rule::create(['name' => $name, 'scope' => 'kost']);
}

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

test('the owner list only shows their own kosts and shows the rejection reason', function () {
    $owner = makeKostOwner();
    $other = makeKostOwner();

    $rejected = Kost::create([
        'owner_id' => $owner->id,
        'name' => 'Kost Melati',
        'address' => 'Jl. Melati No. 1',
        'city' => 'Kota Bandung',
        'latitude' => -6.9,
        'longitude' => 107.6,
        'type' => 'putra',
        'status' => 'ditolak',
        'status_reason' => 'Foto bangunan belum jelas.',
    ]);

    Photo::create([
        'kost_id' => $rejected->id,
        'category' => 'bangunan',
        'photo_path' => 'kosts/depan.webp',
        'sort_order' => 0,
        'is_primary' => true,
    ]);

    Kost::create([
        'owner_id' => $other->id,
        'name' => 'Kost Orang Lain',
        'address' => 'Jl. Lain No. 2',
        'city' => 'Kota Bandung',
        'latitude' => -6.8,
        'longitude' => 107.5,
        'type' => 'putri',
    ]);

    Kost::create([
        'owner_id' => $owner->id,
        'name' => 'Kost Terhapus',
        'address' => 'Jl. Hapus No. 3',
        'city' => 'Kota Bandung',
        'latitude' => -6.7,
        'longitude' => 107.4,
        'type' => 'campur',
    ])->delete();

    $this->actingAs($owner)
        ->get(route('owner.kosts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/kosts/index')
            ->has('kosts.data', 1)
            ->where('kosts.data.0.name', 'Kost Melati')
            ->where('kosts.data.0.status', 'ditolak')
            ->where('kosts.data.0.status_reason', 'Foto bangunan belum jelas.')
            ->where('kosts.data.0.photo_url', Storage::disk('public')->url('kosts/depan.webp'))
        );
});

test('a verified owner can open the submission form with general facilities and kost rules only', function () {
    $owner = makeKostOwner();

    $general = umumFacility('WiFi');
    Facility::create(['name' => 'AC', 'scope' => 'kamar', 'icon' => 'snowflake']);
    $kostRule = kostRule('Dilarang merokok');
    Rule::create(['name' => 'Hanya untuk satu orang', 'scope' => 'kamar']);

    $this->actingAs($owner)
        ->get(route('owner.kosts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/kosts/create')
            ->has('facilities', 1)
            ->where('facilities.0.id', $general->id)
            ->has('rules', 1)
            ->where('rules.0.id', $kostRule->id)
            ->has('geoapifyKey')
        );
});

test('customers, admins, and inactive owners are forbidden', function () {
    $customer = makeKostUser('customer');
    $admin = makeKostUser('admin');
    $inactive = makeKostOwner(['is_active' => false]);

    foreach ([$customer, $admin, $inactive] as $user) {
        $this->actingAs($user)
            ->get(route('owner.kosts.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('owner.kosts.create'))
            ->assertForbidden();
    }
});

test('a complete submission is stored as waiting for verification', function () {
    $owner = makeKostOwner();
    $other = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    $this->actingAs($owner)
        ->post(route('owner.kosts.store'), kostPayload($facility, $rule, [
            'owner_id' => $other->id,
            'status' => 'disetujui',
            'status_reason' => 'Disetujui pemilik.',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.kosts.index'));

    $kost = Kost::query()->sole();

    expect($kost->owner_id)->toBe($owner->id)
        ->and($kost->status)->toBe('menunggu_verifikasi')
        ->and($kost->status_reason)->toBeNull()
        ->and($kost->reviewed_by)->toBeNull()
        ->and($kost->reviewed_at)->toBeNull()
        ->and($kost->description)->toBe('Kost nyaman dekat kampus.');

    expect($kost->photos)->toHaveCount(1);

    $photo = $kost->photos->first();

    expect($photo)->not->toBeNull()
        ->and($photo->category)->toBe('bangunan')
        ->and($photo->room_type_id)->toBeNull()
        ->and($photo->is_primary)->toBeTrue()
        ->and($photo->photo_path)->toEndWith('.webp');

    Storage::disk('public')->assertExists($photo->photo_path);

    expect($kost->facilities->pluck('id')->all())->toBe([$facility->id])
        ->and($kost->kostRules->pluck('id')->all())->toBe([$rule->id]);

    expect($kost->documents)->toHaveCount(2)
        ->and($kost->documents->pluck('type')->sort()->values()->all())
        ->toBe(['nib', 'surat_pernyataan']);

    foreach ($kost->documents as $document) {
        Storage::disk('local')->assertExists($document->file_path);
    }
});

test('a submission without a description is accepted', function () {
    $owner = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    $payload = kostPayload($facility, $rule);
    unset($payload['description']);

    $this->actingAs($owner)
        ->post(route('owner.kosts.store'), $payload)
        ->assertSessionHasNoErrors();

    expect(Kost::query()->sole()->description)->toBeNull();
});

test('coordinates outside Indonesia are rejected on the latitude column', function () {
    $owner = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    $this->actingAs($owner)
        ->post(route('owner.kosts.store'), kostPayload($facility, $rule, [
            'latitude' => '-20',
            'longitude' => '100',
        ]))
        ->assertSessionHasErrors('latitude');

    expect(Kost::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('a submission without photos is rejected', function () {
    $owner = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    $this->actingAs($owner)
        ->post(route('owner.kosts.store'), kostPayload($facility, $rule, [
            'photos' => [],
        ]))
        ->assertSessionHasErrors('photos');

    expect(Kost::query()->count())->toBe(0);
});

test('a submission without NIB is rejected', function () {
    $owner = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    $payload = kostPayload($facility, $rule);
    $payload['documents'] = [
        'surat_pernyataan' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
    ];

    $this->actingAs($owner)
        ->post(route('owner.kosts.store'), $payload)
        ->assertSessionHasErrors('documents.nib');

    expect(Kost::query()->count())->toBe(0);
});

test('a photo larger than the configured limit is rejected', function () {
    $owner = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    $this->actingAs($owner)
        ->post(route('owner.kosts.store'), kostPayload($facility, $rule, [
            'photos' => [
                UploadedFile::fake()->create('besar.jpg', 4096, 'image/jpeg'),
            ],
        ]))
        ->assertSessionHasErrors('photos.0');

    expect(Kost::query()->count())->toBe(0);
});

test('a failed row insert removes every stored file', function () {
    $owner = makeKostOwner();
    $facility = umumFacility();
    $rule = kostRule();

    Kost::creating(function (): void {
        throw new RuntimeException('Penyimpanan gagal.');
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($owner)->post(
        route('owner.kosts.store'),
        kostPayload($facility, $rule),
    ))->toThrow(RuntimeException::class, 'Penyimpanan gagal.');

    expect(Kost::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('the scaled image action stores only a webp file capped at 1600 pixels', function () {
    $path = app(StoreImage::class)->scaled(
        UploadedFile::fake()->image('foto.jpg', 4000, 3000),
    );

    $size = getimagesize(Storage::disk('public')->path($path));

    expect($path)->toEndWith('.webp')
        ->and($size)->not->toBeFalse()
        ->and($size[0])->toBe(1600)
        ->and($size[1])->toBe(1200)
        ->and($size['mime'])->toBe('image/webp')
        ->and(Storage::disk('public')->allFiles())->toHaveCount(1);
});

test('the scaled image action does not enlarge a small photo', function () {
    $path = app(StoreImage::class)->scaled(
        UploadedFile::fake()->image('kecil.jpg', 800, 600),
    );

    $size = getimagesize(Storage::disk('public')->path($path));

    expect($size)->not->toBeFalse()
        ->and($size[0])->toBe(800)
        ->and($size[1])->toBe(600);
});
