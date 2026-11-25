<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @return array<string, array<string, mixed>>
 */
function domainColumns(string $table): array
{
    /** @var array<string, array<string, mixed>> $columns */
    $columns = [];

    foreach (DB::select(
        'select column_name, data_type, column_default, is_nullable
         from information_schema.columns where table_schema = current_schema() and table_name = ?',
        [$table],
    ) as $column) {
        $columns[(string) $column->column_name] = (array) $column;
    }

    return $columns;
}

/**
 * @return array<string, string>
 */
function domainForeignKeys(string $table): array
{
    /** @var array<string, string> $keys */
    $keys = [];

    foreach (DB::select(
        'select kcu.column_name, rc.delete_rule
         from information_schema.table_constraints tc
         join information_schema.key_column_usage kcu
           on kcu.constraint_name = tc.constraint_name and kcu.table_schema = tc.table_schema
         join information_schema.referential_constraints rc
           on rc.constraint_name = tc.constraint_name and rc.constraint_schema = tc.table_schema
         where tc.constraint_type = \'FOREIGN KEY\' and tc.table_schema = current_schema() and tc.table_name = ?',
        [$table],
    ) as $key) {
        $keys[(string) $key->column_name] = (string) $key->delete_rule;
    }

    return $keys;
}

/**
 * @return array<int, string>
 */
function domainPrimaryKey(string $table): array
{
    /** @var array<int, string> $columns */
    $columns = [];

    foreach (DB::select(
        'select kcu.column_name
         from information_schema.table_constraints tc
         join information_schema.key_column_usage kcu
           on kcu.constraint_name = tc.constraint_name and kcu.table_schema = tc.table_schema
         where tc.constraint_type = \'PRIMARY KEY\' and tc.table_schema = current_schema() and tc.table_name = ?
         order by kcu.ordinal_position',
        [$table],
    ) as $column) {
        $columns[] = (string) $column->column_name;
    }

    return $columns;
}

function domainKostId(?User $owner = null): string
{
    $owner ??= User::factory()->create();
    $id = (string) Str::uuid7();

    DB::table('kosts')->insert([
        'id' => $id,
        'owner_id' => $owner->id,
        'name' => 'Kost Melati',
        'address' => 'Jl. Contoh 1',
        'city' => 'Bandung',
        'latitude' => -6.9,
        'longitude' => 107.6,
        'type' => 'putra',
    ]);

    return $id;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function domainRoomTypeId(string $kostId, array $attributes = []): string
{
    $id = (string) Str::uuid7();

    DB::table('room_types')->insert($attributes + [
        'id' => $id,
        'kost_id' => $kostId,
        'name' => 'AC',
        'price_per_month' => 900000,
        'total_rooms' => 5,
        'available_rooms' => 5,
    ]);

    return $id;
}

dataset('domain tables', [
    'roles', 'users', 'whatsapp_verifications', 'kosts', 'room_types', 'photos',
    'kost_documents', 'facilities', 'rules', 'specifications', 'complaints',
    'complaint_attachments', 'notifications',
]);

it('gives every domain table a single uuid primary key without a database default', function (string $table) {
    $primary = domainPrimaryKey($table);

    expect($primary)->toBe(['id'])
        ->and(domainColumns($table)['id']['data_type'])->toBe('uuid')
        ->and(domainColumns($table)['id']['column_default'])->toBeNull();
})->with('domain tables');

it('gives every pivot table a composite primary key and no id column', function (string $table, array $columns) {
    expect(domainPrimaryKey($table))->toBe($columns)
        ->and(domainColumns($table))->not->toHaveKey('id');
})->with([
    'kost_facilities' => ['kost_facilities', ['kost_id', 'facility_id']],
    'kost_rules' => ['kost_rules', ['kost_id', 'rule_id']],
    'room_type_facilities' => ['room_type_facilities', ['room_type_id', 'facility_id']],
    'room_type_rules' => ['room_type_rules', ['room_type_id', 'rule_id']],
    'room_type_specifications' => ['room_type_specifications', ['room_type_id', 'specification_id']],
]);

it('drops the room and kost photo tables and the retired kost columns', function () {
    expect(Schema::hasTable('rooms'))->toBeFalse()
        ->and(Schema::hasTable('kost_photos'))->toBeFalse()
        ->and(Schema::hasColumn('kosts', 'rules'))->toBeFalse();
});

it('applies the timestamp policy of each table', function (string $table, array $expected) {
    $columns = array_keys(domainColumns($table));
    $timestamps = array_values(array_filter(
        $columns,
        fn (string $column) => in_array($column, ['created_at', 'updated_at', 'deleted_at'], true),
    ));

    sort($timestamps);
    sort($expected);

    expect($timestamps)->toBe($expected);
})->with([
    'roles' => ['roles', ['created_at']],
    'users' => ['users', ['created_at', 'updated_at']],
    'whatsapp_verifications' => ['whatsapp_verifications', ['created_at']],
    'kosts' => ['kosts', ['created_at', 'updated_at', 'deleted_at']],
    'room_types' => ['room_types', ['created_at', 'updated_at', 'deleted_at']],
    'facilities' => ['facilities', []],
    'rules' => ['rules', []],
    'specifications' => ['specifications', []],
    'photos' => ['photos', []],
    'kost_documents' => ['kost_documents', []],
    'kost_facilities' => ['kost_facilities', []],
    'kost_rules' => ['kost_rules', []],
    'room_type_facilities' => ['room_type_facilities', []],
    'room_type_rules' => ['room_type_rules', []],
    'room_type_specifications' => ['room_type_specifications', []],
    'complaints' => ['complaints', ['created_at', 'updated_at']],
    'complaint_attachments' => ['complaint_attachments', []],
    'notifications' => ['notifications', ['created_at']],
]);

it('applies the reference actions the ERD declares', function (string $table, array $expected) {
    $keys = domainForeignKeys($table);

    foreach ($expected as $column => $action) {
        expect($keys[$column] ?? null)->toBe($action);
    }
})->with([
    'users' => ['users', ['role_id' => 'RESTRICT']],
    'whatsapp_verifications' => ['whatsapp_verifications', ['user_id' => 'CASCADE']],
    'kosts' => ['kosts', ['owner_id' => 'RESTRICT', 'reviewed_by' => 'SET NULL']],
    'room_types' => ['room_types', ['kost_id' => 'CASCADE']],
    'photos' => ['photos', ['kost_id' => 'CASCADE', 'room_type_id' => 'CASCADE']],
    'kost_documents' => ['kost_documents', ['kost_id' => 'CASCADE']],
    'kost_facilities' => ['kost_facilities', ['kost_id' => 'CASCADE', 'facility_id' => 'RESTRICT']],
    'kost_rules' => ['kost_rules', ['kost_id' => 'CASCADE', 'rule_id' => 'RESTRICT']],
    'room_type_facilities' => ['room_type_facilities', ['room_type_id' => 'CASCADE', 'facility_id' => 'RESTRICT']],
    'room_type_rules' => ['room_type_rules', ['room_type_id' => 'CASCADE', 'rule_id' => 'RESTRICT']],
    'room_type_specifications' => ['room_type_specifications', ['room_type_id' => 'CASCADE', 'specification_id' => 'RESTRICT']],
    'complaints' => ['complaints', ['user_id' => 'RESTRICT', 'kost_id' => 'SET NULL', 'handled_by' => 'SET NULL']],
    'complaint_attachments' => ['complaint_attachments', ['complaint_id' => 'CASCADE']],
    'notifications' => ['notifications', ['user_id' => 'CASCADE']],
]);

it('rejects coordinates outside the documented range', function (string $column, float $value) {
    $owner = User::factory()->create();

    expect(fn () => DB::table('kosts')->insert([
        'id' => (string) Str::uuid7(),
        'owner_id' => $owner->id,
        'name' => 'Kost',
        'address' => 'Jl. Contoh',
        'city' => 'Bandung',
        'latitude' => $column === 'latitude' ? $value : -6.9,
        'longitude' => $column === 'longitude' ? $value : 107.6,
        'type' => 'putra',
    ]))->toThrow(QueryException::class);
})->with([
    'latitude too high' => ['latitude', 100.0],
    'longitude too high' => ['longitude', 200.0],
]);

it('rejects a room type with an invalid price, room total, or availability', function (array $override) {
    $kostId = domainKostId();

    expect(fn () => DB::table('room_types')->insert($override + [
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'name' => 'AC',
        'price_per_month' => 900000,
        'total_rooms' => 5,
        'available_rooms' => 5,
    ]))->toThrow(QueryException::class);
})->with([
    'zero price' => [['price_per_month' => 0]],
    'negative price' => [['price_per_month' => -1]],
    'zero total' => [['total_rooms' => 0]],
    'available over total' => [['available_rooms' => 6]],
    'negative available' => [['available_rooms' => -1]],
]);

it('rejects a duplicate room type name on one kost', function () {
    $kostId = domainKostId();
    domainRoomTypeId($kostId);

    expect(fn () => DB::transaction(fn () => domainRoomTypeId($kostId)))
        ->toThrow(QueryException::class);
});

it('rejects master rows with values outside the documented enumerations', function (string $table, array $row) {
    expect(fn () => DB::table($table)->insert($row + ['id' => (string) Str::uuid7()]))
        ->toThrow(QueryException::class);
})->with([
    'facility scope' => ['facilities', ['name' => 'Kolam', 'scope' => 'publik', 'icon' => 'waves']],
    'rule scope' => ['rules', ['name' => 'Dilarang', 'scope' => 'publik']],
    'specification input type' => ['specifications', ['name' => 'Warna', 'input_type' => 'color']],
]);

it('refuses to delete master data that is still used', function () {
    $kostId = domainKostId();
    $facilityId = (string) Str::uuid7();
    DB::table('facilities')->insert([
        'id' => $facilityId,
        'name' => 'WiFi',
        'scope' => 'umum',
        'icon' => 'wifi',
    ]);
    DB::table('kost_facilities')->insert(['kost_id' => $kostId, 'facility_id' => $facilityId]);

    expect(fn () => DB::transaction(fn () => DB::table('facilities')->where('id', $facilityId)->delete()))
        ->toThrow(QueryException::class);
});

it('rejects a photo whose category and room type do not match', function (string $category, bool $withRoomType) {
    $kostId = domainKostId();
    $roomTypeId = domainRoomTypeId($kostId);

    expect(fn () => DB::table('photos')->insert([
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'room_type_id' => $withRoomType ? $roomTypeId : null,
        'category' => $category,
        'photo_path' => 'kosts/1.jpg',
    ]))->toThrow(QueryException::class);
})->with([
    'building photo with a room type' => ['bangunan', true],
    'common facility photo with a room type' => ['fasilitas_umum', true],
    'room photo without a room type' => ['kamar', false],
    'bathroom photo without a room type' => ['kamar_mandi', false],
    'unknown category' => ['dapur', false],
]);

it('allows exactly one primary photo per level', function (string $level) {
    $kostId = domainKostId();
    $roomTypeId = domainRoomTypeId($kostId);

    $photo = fn () => DB::table('photos')->insert([
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'room_type_id' => $level === 'room type' ? $roomTypeId : null,
        'category' => $level === 'room type' ? 'kamar' : 'bangunan',
        'photo_path' => 'kosts/1.jpg',
        'is_primary' => true,
    ]);

    $photo();

    expect(fn () => DB::transaction(fn () => $photo()))->toThrow(QueryException::class);
})->with(['kost', 'room type']);

it('rejects a duplicate document type on one kost', function () {
    $kostId = domainKostId();

    DB::table('kost_documents')->insert([
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'type' => 'nib',
        'file_path' => 'kosts/nib.pdf',
    ]);

    expect(fn () => DB::transaction(fn () => DB::table('kost_documents')->insert([
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'type' => 'nib',
        'file_path' => 'kosts/nib-2.pdf',
    ])))->toThrow(QueryException::class);
});

it('rejects an unknown document type', function () {
    $kostId = domainKostId();

    expect(fn () => DB::table('kost_documents')->insert([
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'type' => 'ktp',
        'file_path' => 'kosts/ktp.pdf',
    ]))->toThrow(QueryException::class);
});

it('does not create an id default on framework tables', function () {
    expect(Schema::hasColumn('sessions', 'created_at'))->toBeFalse()
        ->and(domainColumns('sessions')['user_id']['data_type'])->toBe('uuid');
});
