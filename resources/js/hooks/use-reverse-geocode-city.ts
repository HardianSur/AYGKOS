import { useEffect } from 'react';
import type { RefObject } from 'react';

type Options = {
    apiKey: string | null;
    latitude: string;
    longitude: string;
    cityEdited: RefObject<boolean>;
    setCity: (key: 'city', value: string) => void;
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

const REVERSE_GEOCODE_DELAY_MS = 600;

export function useReverseGeocodeCity({
    apiKey,
    latitude,
    longitude,
    cityEdited,
    setCity,
}: Options): void {
    useEffect(() => {
        if (apiKey === null || apiKey === '') {
            return;
        }

        if (latitude === '' || longitude === '') {
            return;
        }

        const numericLatitude = Number(latitude);
        const numericLongitude = Number(longitude);

        if (Number.isNaN(numericLatitude) || Number.isNaN(numericLongitude)) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            const params = new URLSearchParams({
                lat: String(numericLatitude),
                lon: String(numericLongitude),
                apiKey,
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
                        !cityEdited.current
                    ) {
                        setCity('city', city);
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
    }, [apiKey, latitude, longitude, cityEdited, setCity]);
}
