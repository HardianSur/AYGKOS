import type { InertiaFormProps } from '@inertiajs/react';
import { ImagePlus } from 'lucide-react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { FormSection } from './form-section';
import { PhotoPreview } from './photo-preview';
import type { KostForm } from './types';

export function PhotosSection({ form }: { form: InertiaFormProps<KostForm> }) {
    const { setData } = form;
    const photoError =
        form.errors.photos ??
        Object.entries(form.errors).find(([key]) =>
            key.startsWith('photos.'),
        )?.[1];

    return (
        <FormSection
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
                        aria-invalid={Boolean(photoError)}
                        aria-describedby={
                            photoError ? 'kost-photos-error' : undefined
                        }
                        onChange={(event) => {
                            setData(
                                'photos',
                                Array.from(event.target.files ?? []),
                            );
                            event.target.value = '';
                        }}
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
                                    onRemove={() =>
                                        setData(
                                            'photos',
                                            form.data.photos.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                />
                            ))}
                        </ul>
                    </>
                )}

                <InputError id="kost-photos-error" message={photoError} />
            </div>
        </FormSection>
    );
}
