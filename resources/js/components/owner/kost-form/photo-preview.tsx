import { X } from 'lucide-react';
import { useEffect, useState } from 'react';

export function PhotoPreview({
    file,
    primary,
    onRemove,
}: {
    file: File;
    primary: boolean;
    onRemove: () => void;
}) {
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
                <button
                    type="button"
                    onClick={onRemove}
                    aria-label={`Hapus foto ${file.name}`}
                    className="absolute top-1.5 right-1.5 flex size-6 items-center justify-center rounded-sm bg-ink text-canvas hover:bg-ink/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
                >
                    <X className="size-3.5" aria-hidden="true" />
                </button>
            </div>
            <p className="truncate text-xs leading-4 text-ink-soft">
                {file.name}
            </p>
        </li>
    );
}
