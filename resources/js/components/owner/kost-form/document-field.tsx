import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { fileClass } from './styles';

export function DocumentField({
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
