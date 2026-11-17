import { useEffect, useState } from 'react';

/**
 * Menunda perubahan nilai selama `delayMs` agar permintaan jaringan tidak
 * dikirim pada setiap ketukan keyboard.
 */
export function useDebouncedValue<T>(value: T, delayMs = 400): T {
    const [debounced, setDebounced] = useState<T>(value);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delayMs);

        return () => clearTimeout(timer);
    }, [delayMs, value]);

    return debounced;
}
