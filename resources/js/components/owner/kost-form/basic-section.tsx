import type { InertiaFormProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { FormSection } from './form-section';
import { fieldClass, textareaClass } from './styles';
import { kostTypes, type KostForm } from './types';

export function BasicSection({ form }: { form: InertiaFormProps<KostForm> }) {
    const { setData } = form;

    return (
        <FormSection
            title="Data dasar"
            description="Nama dan deskripsi yang akan dilihat pencari kost."
        >
            <div className="grid gap-2">
                <Label htmlFor="kost-name" className="text-ink">
                    Nama kost
                </Label>
                <Input
                    id="kost-name"
                    value={form.data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    required
                    maxLength={255}
                    autoComplete="off"
                    placeholder="Contoh: Kost Melati"
                    aria-invalid={Boolean(form.errors.name)}
                    className={fieldClass}
                />
                <InputError message={form.errors.name} />
            </div>

            <fieldset className="grid gap-3">
                <legend className="mb-1 text-sm font-medium text-ink">
                    Tipe kost
                </legend>
                <div className="grid gap-3 sm:grid-cols-3">
                    {kostTypes.map((option) => {
                        const selected = form.data.type === option.value;

                        return (
                            <label
                                key={option.value}
                                className={cn(
                                    'flex cursor-pointer flex-col gap-1 rounded-lg border p-3 transition-colors has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas',
                                    selected
                                        ? 'border-ember-ink bg-ember-wash'
                                        : 'border-field bg-surface hover:bg-canvas',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="type"
                                    value={option.value}
                                    checked={selected}
                                    onChange={() =>
                                        setData('type', option.value)
                                    }
                                    className="sr-only"
                                />
                                <span className="text-sm font-semibold text-ink">
                                    {option.label}
                                </span>
                                <span className="text-xs leading-[1.5] text-ink-soft">
                                    {option.hint}
                                </span>
                            </label>
                        );
                    })}
                </div>
                <InputError message={form.errors.type} />
            </fieldset>

            <div className="grid gap-2">
                <Label htmlFor="kost-description" className="text-ink">
                    Deskripsi{' '}
                    <span className="font-normal text-ink-soft">
                        (opsional)
                    </span>
                </Label>
                <textarea
                    id="kost-description"
                    value={form.data.description}
                    onChange={(event) =>
                        setData('description', event.target.value)
                    }
                    rows={4}
                    maxLength={2000}
                    placeholder="Ceritakan suasana, keunggulan, dan lingkungan sekitar kost."
                    aria-invalid={Boolean(form.errors.description)}
                    className={cn(textareaClass, 'min-h-28')}
                />
                <InputError message={form.errors.description} />
            </div>
        </FormSection>
    );
}
