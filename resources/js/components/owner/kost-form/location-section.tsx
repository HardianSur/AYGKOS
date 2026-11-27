import type { InertiaFormProps } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import LeafletMap, { type MapCenter } from '@/components/map/leaflet-map';
import LocationAutocomplete, {
    type LocationSuggestion,
} from '@/components/map/location-autocomplete';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useReverseGeocodeCity } from '@/hooks/use-reverse-geocode-city';
import { cn } from '@/lib/utils';
import { FormSection } from './form-section';
import { fieldClass, textareaClass } from './styles';
import type { KostForm } from './types';

const DEFAULT_CENTER: MapCenter = { latitude: -2.5, longitude: 118 };

export function LocationSection({
    form,
    geoapifyKey,
}: {
    form: InertiaFormProps<KostForm>;
    geoapifyKey: string | null;
}) {
    const { setData } = form;
    const [center, setCenter] = useState<MapCenter>(DEFAULT_CENTER);
    const cityEditedRef = useRef(form.data.city !== '');

    useReverseGeocodeCity({
        apiKey: geoapifyKey,
        latitude: form.data.latitude,
        longitude: form.data.longitude,
        cityEdited: cityEditedRef,
        setCity: setData,
    });

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

    return (
        <FormSection
            title="Lokasi"
            description="Tandai titik kost pada peta. Titik ini yang dipakai pencari kost untuk menghitung jarak."
        >
            {geoapifyKey === null || geoapifyKey === '' ? (
                <p className="rounded-md bg-[#FDECEA] px-3 py-2 text-sm leading-[22px] text-[#B42318] dark:bg-[#3D1E24] dark:text-[#FF8A80]">
                    Peta belum dikonfigurasi. Hubungi Admin agar kunci Geoapify
                    diisi sebelum mengajukan kost.
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
                        <div>
                            <LocationAutocomplete
                                id="kost-location-search"
                                apiKey={geoapifyKey}
                                onSelect={chooseSuggestion}
                                placeholder="Cari nama tempat, jalan, atau kelurahan..."
                            />
                        </div>
                        <p className="text-xs leading-4 text-ink-soft">
                            Pilih saran untuk mengisi alamat dan kota, atau klik
                            langsung pada peta.
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
                    onChange={(event) => setData('address', event.target.value)}
                    required
                    rows={3}
                    maxLength={500}
                    autoComplete="street-address"
                    placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan."
                    aria-invalid={Boolean(form.errors.address)}
                    className={cn(textareaClass, 'min-h-20')}
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
                                {form.data.latitude}, {form.data.longitude}
                            </span>
                        )}
                    </div>
                    <InputError
                        message={form.errors.latitude ?? form.errors.longitude}
                    />
                </div>
            </div>
        </FormSection>
    );
}
