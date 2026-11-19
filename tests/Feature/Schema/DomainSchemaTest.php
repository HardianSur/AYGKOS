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

dataset('domain tables', [
    'roles', 'users', 'whatsapp_verifications', 'kosts', 'kost_photos',
    'facilities', 'rooms', 'complaints', 'complaint_attachments', 'notifications',
]);

it('gives every domain table a single uuid primary key without a database default', function (string $table) {
    $primary = domainPrimaryKey($table);

    expect($primary)->toBe(['id'])
        ->and(domainColumns($table)['id']['data_type'])->toBe('uuid')
        ->and(domainColumns($table)['id']['column_default'])->toBeNull();
})->with('domain tables');

it('gives kost_facilities a composite primary key and no id column', function () {
    expect(domainPrimaryKey('kost_facilities'))->toBe(['kost_id', 'facility_id'])
        ->and(domainColumns('kost_facilities'))->not->toHaveKey('id');
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
    'kost_photos' => ['kost_photos', []],
    'facilities' => ['facilities', []],
    'kost_facilities' => ['kost_facilities', []],
    'rooms' => ['rooms', ['created_at', 'updated_at', 'deleted_at']],
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
    'kost_photos' => ['kost_photos', ['kost_id' => 'CASCADE']],
    'kost_facilities' => ['kost_facilities', ['kost_id' => 'CASCADE', 'facility_id' => 'CASCADE']],
    'rooms' => ['rooms', ['kost_id' => 'CASCADE']],
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
        'verification_document_path' => 'docs/ktp.pdf',
    ]))->toThrow(QueryException::class);
})->with([
    'latitude too high' => ['latitude', 100.0],
    'longitude too high' => ['longitude', 200.0],
]);

it('rejects a room price that is not positive', function () {
    $owner = User::factory()->create();
    $kostId = (string) Str::uuid7();

    DB::table('kosts')->insert([
        'id' => $kostId,
        'owner_id' => $owner->id,
        'name' => 'Kost',
        'address' => 'Jl. Contoh',
        'city' => 'Bandung',
        'latitude' => -6.9,
        'longitude' => 107.6,
        'type' => 'putra',
        'verification_document_path' => 'docs/ktp.pdf',
    ]);

    expect(fn () => DB::table('rooms')->insert([
        'id' => (string) Str::uuid7(),
        'kost_id' => $kostId,
        'room_number' => 'A1',
        'price_per_month' => 0,
    ]))->toThrow(QueryException::class);
});

it('does not create an id default on framework tables', function () {
    expect(Schema::hasColumn('sessions', 'created_at'))->toBeFalse()
        ->and(domainColumns('sessions')['user_id']['data_type'])->toBe('uuid');
});
