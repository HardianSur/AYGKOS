import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus, MapPin, Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import { FacilityIcon } from '@/components/facility-icon';
import InputError from '@/components/input-error';
import LeafletMap, { type MapCenter } from '@/components/map/leaflet-map';
import LocationAutocomplete, {
    type LocationSuggestion,
} from '@/components/map/location-autocomplete';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { create, index, store } from '@/routes/owner/kosts';

type Facility = {
    id: string;
    name: string;
    icon: string;
};

type Rule = {
    id: string;
    name: string;
};

type KostType = 'putra' | 'putri' | 'campur';

type DocumentKey =
    | 'nib'
    | 'surat_pernyataan'
    | 'domisili_usaha'
    | 'izin_lingkungan'
    | 'izin_kelurahan';

type Documents = Record<DocumentKey, File | null>;

type KostForm = {
    name: string;
    address: string;
    city: string;
    latitude: string;
    longitude: string;
    type: KostType;
    description: string;
    facilities: string[];
    rules: string[];
    photos: File[];
    documents: Documents;
};

/**
 * Respons `format=json` Geoapify bersifat datar: bagian wilayah ada di akar
 * objek, bukan di dalam `properties` seperti format GeoJSON.
 */
type ReverseResult = {
    city?: string;
    county?: string;
    state?: string;
};

const DEFAULT_CENTER: MapCenter = { latitude: -2.5, longitude: 118 };
const REVERSE_GEOCODE_DELAY_MS = 600;

const fieldClass =
    'h-10 rounded-lg border-field bg-surface text-ink placeholder:text-ink-soft focus-visible:border-field focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas';

const fileClass =
    'block w-full rounded-lg border border-field bg-surface text-sm text-ink file:mr-3 file:rounded-md file:border-0 file:bg-ember-wash file:px-3 file:py-2 file:text-sm file:font-medium file:text-ember-ink hover:file:bg-ember-wash/80 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas';

const emptyDocuments: Documents = {
    nib: null,
    surat_pernyataan: null,
    domisili_usaha: null,
    izin_lingkungan: null,
    izin_kelurahan: null,
};

const optionalDocuments: { key: DocumentKey; label: string }[] = [
    { key: 'domisili_usaha', label: 'Surat keterangan domisili usaha' },
    { key: 'izin_lingkungan', label: 'Izin lingkungan RT/RW' },
    { key: 'izin_kelurahan', label: 'Izin kelurahan/kecamatan' },
];

const kostTypes: { value: KostType; label: string; hint: string }[] = [
    { value: 'putra', label: 'Putra', hint: 'Hanya menerima penghuni putra.' },
    { value: 'putri', label: 'Putri', hint: 'Hanya menerima penghuni putri.' },
    {
        value: 'campur',
        label: 'Campur',
        hint: 'Menerima penghuni putra dan putri.',
    },
];

export default function OwnerKostCreate({
    facilities,
    rules,
    geoapifyKey,
}: {
    facilities: Facility[];
    rules: Rule[];
    geoapifyKey: string | null;
}) {
    const form = useForm<KostForm>({
        name: '',
        address: '',
        city: '',
        latitude: '',
        longitude: '',
        type: 'campur',
        description: '',
        facilities: [],
        rules: [],
        photos: [],
        documents: emptyDocuments,
    });

    const [center, setCenter] = useState<MapCenter>(DEFAULT_CENTER);
    const cityEditedRef = useRef(form.data.city !== '');
    const setData = form.setData;

    useEffect(() => {
        if (geoapifyKey === null || geoapifyKey === '') {
            return;
        }

        if (form.data.latitude === '' || form.data.longitude === '') {
            return;
        }

        const latitude = Number(form.data.latitude);
        const longitude = Number(form.data.longitude);

        if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            const params = new URLSearchParams({
                lat: String(latitude),
                lon: String(longitude),
                apiKey: geoapifyKey,
                format: 'json',
                lang: 'id',
                // Tanpa filter ini hasil terdekat bisa berupa kode pos desa
                // (mis. "Baros") alih-alih kota administratifnya.
                type: 'city',
            });

            void (async () => {
                try {
                    const response = await fetch(
                        `https://api.geoapify.com/v1/geocode/reverse?${params.toString()}`,
                        { signal: controller.signal },
                    );

                    if (!response.ok) {
                        return;
                    }

                    const payload = (await response.json()) as {
                        results?: ReverseResult[];
                    };

                    const result = payload.results?.[0];
                    const city =
                        result?.city ?? result?.county ?? result?.state;

                    if (
                        city !== undefined &&
                        city !== '' &&
                        !cityEditedRef.current
                    ) {
                        setData('city', city);
                    }
                } catch {
                    // Kegagalan reverse geocode tidak menghalangi pengajuan.
                }
            })();
        }, REVERSE_GEOCODE_DELAY_MS);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [geoapifyKey, form.data.latitude, form.data.longitude, setData]);

    function applyPoint(
        point: MapCenter,
        extra?: { address?: string; city?: string },
    ): void {
        setCenter(point);

        setData((data) => ({
            ...data,
            latitude: point.latitude.toFixed(7),
            longitude: point.longitude.toFixed(7),
            ...(extra?.address !== undefined && data.address.trim() === ''
                ? { address: extra.address }
                : {}),
            ...(extra?.city !== undefined && extra.city !== ''
                ? { city: extra.city }
                : {}),
        }));
    }

    function chooseSuggestion(suggestion: LocationSuggestion): void {
        applyPoint(
            {
                latitude: suggestion.latitude,
                longitude: suggestion.longitude,
            },
            {
                address: suggestion.address,
                city: suggestion.city,
            },
        );
    }

    function toggleSelection(
        key: 'facilities' | 'rules',
        id: string,
        checked: boolean,
    ): void {
        const current = form.data[key];

        setData(
            key,
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post(store.url(), { forceFormData: true });
    }

    const photoError = form.errors.photos ?? form.errors['photos.0'];

    return (
        <>
            <Head title="Ajukan Kost" />

            <div className="flex flex-1 flex-col gap-6 p-4 font-brand md:p-6">
                <header className="space-y-2">
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1.5 rounded-sm text-sm leading-[22px] font-medium text-ember-ink hover:underline focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Kembali ke Kost Saya
                    </Link>
                    <h1 className="text-2xl leading-[30px] font-bold text-ink">
                        Ajukan Kost
                    </h1>
                    <p className="max-w-[70ch] text-sm leading-[22px] text-ink-soft">
                        Lengkapi data kost, tandai titik lokasinya pada peta,
                        lalu unggah foto dan dokumen izin. Pengajuan akan
                        menunggu verifikasi Admin sebelum tampil kepada pencari
                        kost.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-6">
                    <Section
                        title="Data dasar"
                        description="Nama dan deskripsi yang akan dilihat pencari kost."
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="kost-name" className="text-ink">
                                Nama kost
                            </Label>
                            <Input
                                id="kost-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                maxLength={255}
                                autoComplete="off"
                                placeholder="Contoh: Kost Melati"
                                aria-invalid={Boolean(form.errors.name)}
                                className={fieldClass}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <fieldset className="grid gap-3">
                            <legend className="mb-1 text-sm font-medium text-ink">
                                Tipe kost
                            </legend>
                            <div className="grid gap-3 sm:grid-cols-3">
                                {kostTypes.map((option) => {
                                    const selected =
                                        form.data.type === option.value;

                                    return (
                                        <label
                                            key={option.value}
                                            className={cn(
                                                'flex cursor-pointer flex-col gap-1 rounded-lg border p-3 transition-colors has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas',
                                                selected
                                                    ? 'border-ember-ink bg-ember-wash'
                                                    : 'border-field bg-surface hover:bg-canvas',
                                            )}
                                        >
                                            <input
                                                type="radio"
                                                name="type"
                                                value={option.value}
                                                checked={selected}
                                                onChange={() =>
                                                    setData(
                                                        'type',
                                                        option.value,
                                                    )
                                                }
                                                className="sr-only"
                                            />
                                            <span className="text-sm font-semibold text-ink">
                                                {option.label}
                                            </span>
                                            <span className="text-xs leading-[1.5] text-ink-soft">
                                                {option.hint}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <InputError message={form.errors.type} />
                        </fieldset>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="kost-description"
                                className="text-ink"
                            >
                                Deskripsi{' '}
                                <span className="font-normal text-ink-soft">
                                    (opsional)
                                </span>
                            </Label>
                            <textarea
                                id="kost-description"
                                value={form.data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                rows={4}
                                maxLength={2000}
                                placeholder="Ceritakan suasana, keunggulan, dan lingkungan sekitar kost."
                                aria-invalid={Boolean(form.errors.description)}
                                className="min-h-28 w-full rounded-lg border border-field bg-surface px-3 py-2 text-sm leading-[22px] text-ink placeholder:text-ink-soft focus-visible:border-field focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
                            />
                            <InputError message={form.errors.description} />
                        </div>
                    </Section>

                    <Section
                        title="Lokasi"
                        description="Tandai titik kost pada peta. Titik ini yang dipakai pencari kost untuk menghitung jarak."
                    >
                        {geoapifyKey === null || geoapifyKey === '' ? (
                            <p className="rounded-md bg-[#FDECEA] px-3 py-2 text-sm leading-[22px] text-[#B42318] dark:bg-[#3D1E24] dark:text-[#FF8A80]">
                                Peta belum dikonfigurasi. Hubungi Admin agar
                                kunci Geoapify diisi sebelum mengajukan kost.
                            </p>
                        ) : (
                            <>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="kost-location-search"
                                        className="text-ink"
                                    >
                                        Cari lokasi
                                    </Label>
                                    <div id="kost-location-search">
                                        <LocationAutocomplete
                                            apiKey={geoapifyKey}
                                            onSelect={chooseSuggestion}
                                            placeholder="Cari nama tempat, jalan, atau kelurahan..."
                                        />
                                    </div>
                                    <p className="text-xs leading-4 text-ink-soft">
                                        Pilih saran untuk mengisi alamat dan
                                        kota, atau klik langsung pada peta.
                                    </p>
                                </div>

                                <LeafletMap
                                    apiKey={geoapifyKey}
                                    center={center}
                                    onPick={applyPoint}
                                    className="h-80 w-full rounded-lg border border-hairline"
                                />
                            </>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="kost-address" className="text-ink">
                                Alamat lengkap
                            </Label>
                            <textarea
                                id="kost-address"
                                value={form.data.address}
                                onChange={(event) =>
                                    setData('address', event.target.value)
                                }
                                required
                                rows={3}
                                maxLength={500}
                                autoComplete="street-address"
                                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan."
                                aria-invalid={Boolean(form.errors.address)}
                                className="min-h-20 w-full rounded-lg border border-field bg-surface px-3 py-2 text-sm leading-[22px] text-ink placeholder:text-ink-soft focus-visible:border-field focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
                            />
                            <InputError message={form.errors.address} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="kost-city" className="text-ink">
                                    Kota
                                </Label>
                                <Input
                                    id="kost-city"
                                    value={form.data.city}
                                    onChange={(event) => {
                                        cityEditedRef.current = true;
                                        setData('city', event.target.value);
                                    }}
                                    required
                                    maxLength={100}
                                    autoComplete="address-level2"
                                    placeholder="Contoh: Kota Bandung"
                                    aria-invalid={Boolean(form.errors.city)}
                                    className={fieldClass}
                                />
                                <InputError message={form.errors.city} />
                            </div>

                            <div className="grid gap-2">
                                <span className="text-sm font-medium text-ink">
                                    Titik peta
                                </span>
                                <div className="flex h-10 items-center gap-2 rounded-lg border border-hairline bg-canvas px-3 text-sm text-ink-soft tabular-nums">
                                    <MapPin
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {form.data.latitude === '' ||
                                    form.data.longitude === '' ? (
                                        <span>Belum dipilih</span>
                                    ) : (
                                        <span>
                                            {form.data.latitude},{' '}
                                            {form.data.longitude}
                                        </span>
                                    )}
                                </div>
                                <InputError
                                    message={
                                        form.errors.latitude ??
                                        form.errors.longitude
                                    }
                                />
                            </div>
                        </div>
                    </Section>

                    <Section
                        title="Fasilitas umum"
                        description="Pilih fasilitas yang tersedia untuk seluruh penghuni kost."
                    >
                        {facilities.length === 0 ? (
                            <p className="text-sm leading-[22px] text-ink-soft">
                                Belum ada fasilitas umum pada master data.
                            </p>
                        ) : (
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {facilities.map((facility) => {
                                    const checked =
                                        form.data.facilities.includes(
                                            facility.id,
                                        );

                                    return (
                                        <label
                                            key={facility.id}
                                            className="flex cursor-pointer items-center gap-3 rounded-lg border border-hairline bg-surface p-3 has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas"
                                        >
                                            <Checkbox
                                                checked={checked}
                                                onCheckedChange={(value) =>
                                                    toggleSelection(
                                                        'facilities',
                                                        facility.id,
                                                        value === true,
                                                    )
                                                }
                                            />
                                            <span className="flex items-center gap-2 text-sm font-medium text-ink">
                                                <FacilityIcon
                                                    name={facility.icon}
                                                    className="size-4 text-ember-ink"
                                                />
                                                {facility.name}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                        )}
                        <InputError message={form.errors.facilities} />
                    </Section>

                    <Section
                        title="Peraturan kost"
                        description="Aturan yang berlaku untuk seluruh penghuni."
                    >
                        {rules.length === 0 ? (
                            <p className="text-sm leading-[22px] text-ink-soft">
                                Belum ada peraturan pada master data.
                            </p>
                        ) : (
                            <div className="grid gap-2">
                                {rules.map((rule) => {
                                    const checked = form.data.rules.includes(
                                        rule.id,
                                    );

                                    return (
                                        <label
                                            key={rule.id}
                                            className="flex cursor-pointer items-center gap-3 rounded-lg border border-hairline bg-surface p-3 has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas"
                                        >
                                            <Checkbox
                                                checked={checked}
                                                onCheckedChange={(value) =>
                                                    toggleSelection(
                                                        'rules',
                                                        rule.id,
                                                        value === true,
                                                    )
                                                }
                                            />
                                            <span className="text-sm text-ink">
                                                {rule.name}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                        )}
                        <InputError message={form.errors.rules} />
                    </Section>

                    <Section
                        title="Foto bangunan"
                        description="Unggah 1 sampai 10 foto. Foto pertama menjadi foto utama."
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="kost-photos" className="text-ink">
                                Foto kost
                            </Label>
                            <label
                                htmlFor="kost-photos"
                                className="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-field bg-canvas px-4 py-8 text-center hover:bg-surface has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas"
                            >
                                <ImagePlus
                                    className="size-7 text-ember-ink"
                                    aria-hidden="true"
                                />
                                <span className="text-sm font-medium text-ink">
                                    Pilih foto
                                </span>
                                <span className="text-xs leading-4 text-ink-soft">
                                    JPG, PNG, atau WebP. Maksimal 3 MB per foto.
                                </span>
                                <input
                                    id="kost-photos"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    onChange={(event) =>
                                        setData(
                                            'photos',
                                            Array.from(
                                                event.target.files ?? [],
                                            ),
                                        )
                                    }
                                    className="sr-only"
                                />
                            </label>

                            {form.data.photos.length > 0 && (
                                <>
                                    <p className="text-sm leading-[22px] text-ink-soft">
                                        {form.data.photos.length} foto dipilih.
                                    </p>

                                    <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        {form.data.photos.map((file, index) => (
                                            <PhotoPreview
                                                key={`${file.name}-${file.lastModified}`}
                                                file={file}
                                                primary={index === 0}
                                            />
                                        ))}
                                    </ul>
                                </>
                            )}

                            <InputError message={photoError} />
                        </div>
                    </Section>

                    <Section
                        title="Dokumen izin"
                        description="Dokumen berformat PDF dengan ukuran maksimal 5 MB per berkas."
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <DocumentField
                                id="document-nib"
                                label="NIB"
                                required
                                file={form.data.documents.nib}
                                error={form.errors['documents.nib']}
                                onChange={(file) =>
                                    setData('documents', {
                                        ...form.data.documents,
                                        nib: file,
                                    })
                                }
                            />

                            <DocumentField
                                id="document-surat-pernyataan"
                                label="Surat pernyataan pemilik bangunan"
                                required
                                file={form.data.documents.surat_pernyataan}
                                error={
                                    form.errors['documents.surat_pernyataan']
                                }
                                onChange={(file) =>
                                    setData('documents', {
                                        ...form.data.documents,
                                        surat_pernyataan: file,
                                    })
                                }
                            />

                            {optionalDocuments.map((document) => (
                                <DocumentField
                                    key={document.key}
                                    id={`document-${document.key}`}
                                    label={document.label}
                                    file={form.data.documents[document.key]}
                                    error={
                                        form.errors[`documents.${document.key}`]
                                    }
                                    onChange={(file) =>
                                        setData('documents', {
                                            ...form.data.documents,
                                            [document.key]: file,
                                        })
                                    }
                                />
                            ))}
                        </div>
                    </Section>

                    <div className="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:justify-end">
                        <Button
                            type="button"
                            variant="secondary"
                            asChild
                            className="h-10 rounded-lg"
                        >
                            <Link href={index()}>Batal</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-10 rounded-lg bg-ember font-medium text-[#1B2038] shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:text-[#12152A]"
                        >
                            <Upload className="size-4" />
                            {form.processing
                                ? 'Mengirim pengajuan…'
                                : 'Kirim pengajuan'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-5 rounded-xl border border-hairline bg-surface p-5 sm:p-6">
            <header className="space-y-1">
                <h2 className="text-lg leading-6 font-bold text-ink">
                    {title}
                </h2>
                <p className="max-w-[70ch] text-sm leading-[22px] text-ink-soft">
                    {description}
                </p>
            </header>
            {children}
        </section>
    );
}

function DocumentField({
    id,
    label,
    required = false,
    file,
    error,
    onChange,
}: {
    id: string;
    label: string;
    required?: boolean;
    file: File | null;
    error?: string;
    onChange: (file: File | null) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className="text-ink">
                {label}{' '}
                {required ? (
                    <span className="text-danger" aria-hidden="true">
                        *
                    </span>
                ) : (
                    <span className="font-normal text-ink-soft">
                        (opsional)
                    </span>
                )}
            </Label>
            <input
                id={id}
                type="file"
                accept="application/pdf"
                onChange={(event) => onChange(event.target.files?.[0] ?? null)}
                aria-invalid={Boolean(error)}
                className={fileClass}
            />
            {file !== null && (
                <p className="truncate text-xs leading-4 text-ink-soft">
                    Terpilih: {file.name}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}

function PhotoPreview({ file, primary }: { file: File; primary: boolean }) {
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        const objectUrl = URL.createObjectURL(file);
        setUrl(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [file]);

    return (
        <li className="grid gap-1.5">
            <div className="relative aspect-square overflow-hidden rounded-lg border border-hairline bg-canvas">
                {url !== null && (
                    <img
                        src={url}
                        alt={file.name}
                        loading="lazy"
                        className="size-full object-cover"
                    />
                )}
                {primary && (
                    <span className="absolute top-1.5 left-1.5 rounded-sm bg-ink px-1.5 py-0.5 text-[11px] leading-4 font-medium text-canvas">
                        Utama
                    </span>
                )}
            </div>
            <p className="truncate text-xs leading-4 text-ink-soft">
                {file.name}
            </p>
        </li>
    );
}

OwnerKostCreate.layout = {
    breadcrumbs: [
        {
            title: 'Kost Saya',
            href: index.url(),
        },
        {
            title: 'Ajukan Kost',
            href: create.url(),
        },
    ],
};
