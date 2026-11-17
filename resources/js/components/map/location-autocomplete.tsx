import { useEffect, useRef, useState } from 'react';
import { useDebouncedValue } from '@/hooks/use-debounced-value';

export type LocationSuggestion = {
    latitude: number;
    longitude: number;
    label: string;
};

type LocationAutocompleteProps = {
    apiKey: string;
    onSelect: (location: LocationSuggestion) => void;
    placeholder?: string;
    minLength?: number;
};

type GeoapifyResult = {
    properties: {
        lat: number;
        lon: number;
        formatted: string;
    };
};

const DEBOUNCE_MS = 400;
const DEFAULT_MIN_LENGTH = 3;

/**
 * Input lokasi dengan autocomplete Geoapify. Permintaan baru hanya dikirim
 * setelah masukan berhenti berubah selama 400 ms, dan permintaan lama
 * dibatalkan agar tidak membuang kredit API.
 */
export default function LocationAutocomplete({
    apiKey,
    onSelect,
    placeholder = 'Cari nama tempat atau alamat...',
    minLength = DEFAULT_MIN_LENGTH,
}: LocationAutocompleteProps) {
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState<LocationSuggestion[]>([]);
    const [loading, setLoading] = useState(false);
    const [quotaExceeded, setQuotaExceeded] = useState(false);
    const abortRef = useRef<AbortController | null>(null);
    const debouncedQuery = useDebouncedValue(query, DEBOUNCE_MS);

    useEffect(() => {
        abortRef.current?.abort();

        if (quotaExceeded || debouncedQuery.trim().length < minLength) {
            setSuggestions([]);
            setLoading(false);

            return;
        }

        const controller = new AbortController();
        abortRef.current = controller;

        const params = new URLSearchParams({
            text: debouncedQuery.trim(),
            apiKey,
            format: 'json',
            limit: '5',
        });

        void (async () => {
            setLoading(true);

            try {
                const response = await fetch(
                    `https://api.geoapify.com/v1/geocode/autocomplete?${params.toString()}`,
                    { signal: controller.signal },
                );

                if (response.status === 429) {
                    setQuotaExceeded(true);
                    setSuggestions([]);

                    return;
                }

                if (!response.ok) {
                    setSuggestions([]);

                    return;
                }

                const data = (await response.json()) as {
                    results?: GeoapifyResult[];
                };

                setSuggestions(
                    (data.results ?? []).map((result) => ({
                        latitude: result.properties.lat,
                        longitude: result.properties.lon,
                        label: result.properties.formatted,
                    })),
                );
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                setSuggestions([]);
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        })();

        return () => controller.abort();
    }, [apiKey, debouncedQuery, minLength, quotaExceeded]);

    const choose = (suggestion: LocationSuggestion) => {
        setQuery(suggestion.label);
        setSuggestions([]);
        onSelect(suggestion);
    };

    return (
        <div className="relative w-full">
            <input
                type="text"
                value={query}
                disabled={quotaExceeded}
                placeholder={placeholder}
                onChange={(event) => setQuery(event.target.value)}
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />

            {loading && (
                <p className="mt-1 text-xs text-muted-foreground">
                    Mencari lokasi...
                </p>
            )}

            {quotaExceeded && (
                <p className="mt-1 text-xs text-muted-foreground">
                    Saran lokasi sementara tidak tersedia. Anda tetap dapat
                    menentukan lokasi secara manual.
                </p>
            )}

            {suggestions.length > 0 && (
                <ul className="absolute z-1000 mt-1 w-full rounded-md border bg-background shadow-lg">
                    {suggestions.map((suggestion) => (
                        <li
                            key={`${suggestion.latitude},${suggestion.longitude}`}
                        >
                            <button
                                type="button"
                                onClick={() => choose(suggestion)}
                                className="w-full px-3 py-2 text-left text-sm hover:bg-accent"
                            >
                                {suggestion.label}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
