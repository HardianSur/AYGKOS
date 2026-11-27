import { useEffect, useRef, useState } from 'react';
import type {
    LayerGroup as LeafletLayerGroup,
    Map as LeafletMapInstance,
} from 'leaflet';

export type MapMarker = {
    id: string | number;
    latitude: number;
    longitude: number;
    label: string;
};

export type MapCenter = {
    latitude: number;
    longitude: number;
};

type LeafletMapProps = {
    apiKey: string;
    center?: MapCenter;
    markers?: MapMarker[];
    radiusKm?: number;
    onPick?: (center: MapCenter) => void;
    className?: string;
};

const DEFAULT_CENTER: MapCenter = { latitude: -2.5, longitude: 118 };
const EMPTY_MARKERS: MapMarker[] = [];
const OVERVIEW_ZOOM = 5;
const RADIUS_ZOOM = 11;
const PICK_ZOOM = 16;

/**
 * Peta Leaflet yang dimuat secara dinamis dan hanya dipasang setelah
 * komponen ter-mount di klien. Leaflet mengakses `window` saat modul
 * dievaluasi, sehingga impor statis akan merusak prerender/SSR.
 *
 * Pemasangan peta tidak bergantung pada `center`; perubahan titik ditangani
 * effect terpisah lewat `flyTo` agar instance peta tidak dibuang dan
 * dibangun ulang pada setiap pemilihan titik.
 */
export default function LeafletMap({
    apiKey,
    center,
    markers = EMPTY_MARKERS,
    radiusKm,
    onPick,
    className,
}: LeafletMapProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const mapRef = useRef<LeafletMapInstance | null>(null);
    const overlaysRef = useRef<LeafletLayerGroup | null>(null);
    const initialCenterRef = useRef(center ?? DEFAULT_CENTER);
    const onPickRef = useRef(onPick);
    const [ready, setReady] = useState(false);

    const pickable = onPick !== undefined;
    const latitude = center?.latitude;
    const longitude = center?.longitude;

    onPickRef.current = onPick;

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            const L = await import('leaflet');
            await import('leaflet/dist/leaflet.css');

            if (cancelled || !containerRef.current || mapRef.current) {
                return;
            }

            const initialCenter = initialCenterRef.current;
            const map = L.map(containerRef.current, {
                center: [initialCenter.latitude, initialCenter.longitude],
                zoom: radiusKm === undefined ? OVERVIEW_ZOOM : RADIUS_ZOOM,
            });
            mapRef.current = map;
            overlaysRef.current = L.layerGroup().addTo(map);

            map.on('click', (event) => {
                onPickRef.current?.({
                    latitude: event.latlng.lat,
                    longitude: event.latlng.lng,
                });
            });

            L.tileLayer(
                `https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png?apiKey=${encodeURIComponent(apiKey)}`,
                {
                    attribution:
                        '&copy; OpenStreetMap contributors &copy; Geoapify',
                    maxZoom: 19,
                },
            ).addTo(map);

            setReady(true);
        })();

        return () => {
            cancelled = true;
            setReady(false);
            mapRef.current?.remove();
            mapRef.current = null;
            overlaysRef.current = null;
        };
    }, [apiKey, radiusKm]);

    useEffect(() => {
        const map = mapRef.current;

        if (!ready || map === null || latitude === undefined) {
            return;
        }

        if (longitude === undefined) {
            return;
        }

        const isDefaultCenter =
            latitude === DEFAULT_CENTER.latitude &&
            longitude === DEFAULT_CENTER.longitude;

        const zoom = isDefaultCenter
            ? radiusKm === undefined
                ? OVERVIEW_ZOOM
                : RADIUS_ZOOM
            : pickable
              ? PICK_ZOOM
              : map.getZoom();

        map.flyTo([latitude, longitude], zoom, {
            duration: 0.6,
        });
    }, [latitude, longitude, pickable, radiusKm, ready]);

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            const L = await import('leaflet');
            const overlays = overlaysRef.current;

            if (cancelled || !overlays) {
                return;
            }

            overlays.clearLayers();

            for (const marker of markers) {
                L.marker([marker.latitude, marker.longitude])
                    .bindPopup(marker.label)
                    .addTo(overlays);
            }

            if (latitude === undefined || longitude === undefined) {
                return;
            }

            if (radiusKm !== undefined) {
                L.circle([latitude, longitude], {
                    radius: radiusKm * 1000,
                    color: '#4B5563',
                    fillOpacity: 0.05,
                }).addTo(overlays);
            }

            if (pickable) {
                const pickMarker = L.marker([latitude, longitude], {
                    draggable: true,
                }).addTo(overlays);

                pickMarker.on('dragend', () => {
                    const position = pickMarker.getLatLng();

                    onPickRef.current?.({
                        latitude: position.lat,
                        longitude: position.lng,
                    });
                });
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [latitude, longitude, markers, radiusKm, ready, pickable]);

    return (
        <div
            ref={containerRef}
            className={className ?? 'h-96 w-full rounded-lg border'}
        />
    );
}
