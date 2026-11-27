import { useEffect, useId, useRef, useState } from 'react';
import { useDebouncedValue } from '@/hooks/use-debounced-value';

export type LocationSuggestion = {
    latitude: number;
    longitude: number;
    label: string;
    city: string;
    address: string;
};

type LocationAutocompleteProps = {
    id?: string;
    apiKey: string;
    onSelect: (location: LocationSuggestion) => void;
    placeholder?: string;
    minLength?: number;
};

/**
 * Respons `format=json` Geoapify bersifat datar: koordinat dan bagian
 * wilayah ada di akar objek, bukan di dalam `properties` seperti format
 * GeoJSON.
 */
type GeoapifyResult = {
    lat: number;
    lon: number;
    formatted: string;
    city?: string;
    county?: string;
    state?: string;
};

const DEBOUNCE_MS = 400;
const DEFAULT_MIN_LENGTH = 3;
const MAX_SUGGESTIONS = 10;

/**
 * Input lokasi dengan autocomplete Geoapify. Permintaan baru hanya dikirim
 * setelah masukan berhenti berubah selama 400 ms, dan permintaan lama
 * dibatalkan agar tidak membuang kredit API.
 */
export default function LocationAutocomplete({
    id,
    apiKey,
    onSelect,
    placeholder = 'Cari nama tempat atau alamat...',
    minLength = DEFAULT_MIN_LENGTH,
}: LocationAutocompleteProps) {
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState<LocationSuggestion[]>([]);
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [loading, setLoading] = useState(false);
    const [quotaExceeded, setQuotaExceeded] = useState(false);
    const abortRef = useRef<AbortController | null>(null);
    const selectedLabelRef = useRef<string | null>(null);
    const debouncedQuery = useDebouncedValue(query, DEBOUNCE_MS);
    const listId = useId();

    useEffect(() => {
        abortRef.current?.abort();

        if (selectedLabelRef.current === debouncedQuery.trim()) {
            setSuggestions([]);
            setLoading(false);
            setOpen(false);

            return;
        }

        if (quotaExceeded || debouncedQuery.trim().length < minLength) {
            setSuggestions([]);
            setLoading(false);
            setOpen(false);

            return;
        }

        const controller = new AbortController();
        abortRef.current = controller;

        const params = new URLSearchParams({
            text: debouncedQuery.trim(),
            apiKey,
            format: 'json',
            limit: String(MAX_SUGGESTIONS),
            filter: 'countrycode:id',
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
                    setOpen(false);

                    return;
                }

                if (!response.ok) {
                    setSuggestions([]);
                    setOpen(false);

                    return;
                }

                const data = (await response.json()) as {
                    results?: GeoapifyResult[];
                };

                if (controller.signal.aborted) {
                    return;
                }

                const results = (data.results ?? [])
                    .slice(0, MAX_SUGGESTIONS)
                    .map((result) => ({
                        latitude: result.lat,
                        longitude: result.lon,
                        label: result.formatted,
                        city:
                            result.city ?? result.county ?? result.state ?? '',
                        address: result.formatted,
                    }));

                setSuggestions(results);
                setActiveIndex(-1);
                setOpen(results.length > 0);
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                setSuggestions([]);
                setOpen(false);
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        })();

        return () => controller.abort();
    }, [apiKey, debouncedQuery, minLength, quotaExceeded]);

    const choose = (suggestion: LocationSuggestion) => {
        abortRef.current?.abort();
        abortRef.current = null;
        selectedLabelRef.current = suggestion.label;
        setQuery(suggestion.label);
        setSuggestions([]);
        setLoading(false);
        setOpen(false);
        setActiveIndex(-1);
        onSelect(suggestion);
    };

    const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Escape') {
            setOpen(false);

            return;
        }

        if (!open || suggestions.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) => (index + 1) % suggestions.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex(
                (index) =>
                    (index - 1 + suggestions.length) % suggestions.length,
            );
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            choose(suggestions[activeIndex]);
        }
    };

    return (
        <div className="relative w-full">
            <input
                id={id}
                type="text"
                value={query}
                placeholder={placeholder}
                autoComplete="off"
                role="combobox"
                aria-expanded={open}
                aria-controls={listId}
                aria-autocomplete="list"
                aria-activedescendant={
                    activeIndex >= 0 ? `${listId}-${activeIndex}` : undefined
                }
                onChange={(event) => {
                    selectedLabelRef.current = null;
                    setQuery(event.target.value);
                }}
                onFocus={() => {
                    if (suggestions.length > 0) {
                        setOpen(true);
                    }
                }}
                onBlur={() => window.setTimeout(() => setOpen(false), 150)}
                onKeyDown={handleKeyDown}
                className="h-10 w-full rounded-lg border border-field bg-surface px-3 text-sm text-ink placeholder:text-ink-soft focus-visible:border-field focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
            />

            {loading && (
                <p className="mt-1 text-xs text-ink-soft">Mencari lokasi...</p>
            )}

            {quotaExceeded && (
                <p className="mt-1 text-xs text-ink-soft">
                    Saran lokasi sementara tidak tersedia. Anda tetap dapat
                    menentukan lokasi secara manual.
                </p>
            )}

            {open && suggestions.length > 0 && (
                <ul
                    id={listId}
                    role="listbox"
                    className="absolute top-full left-0 z-[1200] mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-hairline bg-surface shadow-lg"
                >
                    {suggestions.map((suggestion, index) => (
                        <li
                            key={`${suggestion.label}-${index}`}
                            id={`${listId}-${index}`}
                            role="option"
                            aria-selected={index === activeIndex}
                        >
                            <button
                                type="button"
                                onClick={() => choose(suggestion)}
                                onMouseEnter={() => setActiveIndex(index)}
                                className={`w-full px-3 py-2 text-left text-sm leading-[22px] ${
                                    index === activeIndex
                                        ? 'bg-ember-wash text-ember-ink'
                                        : 'text-ink hover:bg-canvas'
                                }`}
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
