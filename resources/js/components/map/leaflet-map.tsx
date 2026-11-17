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
    className?: string;
};

const DEFAULT_CENTER: MapCenter = { latitude: -2.5, longitude: 118 };

/**
 * Peta Leaflet yang dimuat secara dinamis dan hanya dipasang setelah
 * komponen ter-mount di klien. Leaflet mengakses `window` saat modul
 * dievaluasi, sehingga impor statis akan merusak prerender/SSR.
 */
export default function LeafletMap({
    apiKey,
    center = DEFAULT_CENTER,
    markers = [],
    radiusKm,
    className,
}: LeafletMapProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const mapRef = useRef<LeafletMapInstance | null>(null);
    const overlaysRef = useRef<LeafletLayerGroup | null>(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            const L = await import('leaflet');
            await import('leaflet/dist/leaflet.css');

            if (cancelled || !containerRef.current || mapRef.current) {
                return;
            }

            const map = L.map(containerRef.current, {
                center: [center.latitude, center.longitude],
                zoom: radiusKm === undefined ? 5 : 11,
            });
            mapRef.current = map;
            overlaysRef.current = L.layerGroup().addTo(map);

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
    }, [apiKey, center.latitude, center.longitude, radiusKm]);

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            const L = await import('leaflet');
            const overlays = overlaysRef.current;

            if (cancelled || !overlays) {
                return;
            }

            overlays.clearLayers();

            if (radiusKm !== undefined) {
                L.circle([center.latitude, center.longitude], {
                    radius: radiusKm * 1000,
                    color: '#4B5563',
                    fillOpacity: 0.05,
                }).addTo(overlays);
            }

            for (const marker of markers) {
                L.marker([marker.latitude, marker.longitude])
                    .bindPopup(marker.label)
                    .addTo(overlays);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [center.latitude, center.longitude, markers, radiusKm, ready]);

    return (
        <div
            ref={containerRef}
            className={className ?? 'h-96 w-full rounded-lg border'}
        />
    );
}
