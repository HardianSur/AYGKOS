<?php

namespace App\Actions\Kost;

use App\Actions\StoreImage;
use App\Models\Kost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SubmitKost
{
    public function __construct(private readonly StoreImage $storeImage) {}

    /**
     * Store a new kost submission with its photos, documents, facilities,
     * and rules. Files are written first, then the rows inside a transaction.
     * Any failure removes the files that were already written.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $owner, array $data): Kost
    {
        $photoPaths = [];
        $documentPaths = [];

        try {
            foreach ($this->photos($data) as $file) {
                $photoPaths[] = $this->storeImage->scaled($file);
            }

            foreach ($this->documents($data) as $type => $file) {
                $path = $file->store('kost-documents', 'local');

                if ($path === false) {
                    throw new RuntimeException('The document could not be stored.');
                }

                $documentPaths[$type] = $path;
            }

            return DB::transaction(function () use ($owner, $data, $photoPaths, $documentPaths): Kost {
                $kost = Kost::create([
                    'owner_id' => $owner->id,
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'type' => $data['type'],
                    'description' => $data['description'] ?? null,
                    'status' => 'menunggu_verifikasi',
                ]);

                foreach ($photoPaths as $index => $path) {
                    $kost->photos()->create([
                        'category' => 'bangunan',
                        'photo_path' => $path,
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                    ]);
                }

                $facilityIds = $this->ids($data, 'facilities');

                if ($facilityIds !== []) {
                    $kost->facilities()->attach($facilityIds);
                }

                $ruleIds = $this->ids($data, 'rules');

                if ($ruleIds !== []) {
                    $kost->kostRules()->attach($ruleIds);
                }

                foreach ($documentPaths as $type => $path) {
                    $kost->documents()->create([
                        'type' => $type,
                        'file_path' => $path,
                    ]);
                }

                return $kost;
            });
        } catch (Throwable $exception) {
            foreach ($photoPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            foreach ($documentPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<UploadedFile>
     */
    private function photos(array $data): array
    {
        $photos = $data['photos'] ?? [];

        if (! is_array($photos)) {
            return [];
        }

        $files = [];

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $files[] = $photo;
            }
        }

        return $files;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, UploadedFile>
     */
    private function documents(array $data): array
    {
        $documents = $data['documents'] ?? [];

        if (! is_array($documents)) {
            return [];
        }

        $files = [];

        foreach ($documents as $type => $document) {
            if (is_string($type) && $document instanceof UploadedFile) {
                $files[$type] = $document;
            }
        }

        return $files;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function ids(array $data, string $key): array
    {
        $values = $data[$key] ?? [];

        if (! is_array($values)) {
            return [];
        }

        $ids = [];

        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $ids[] = $value;
            }
        }

        return $ids;
    }
}
