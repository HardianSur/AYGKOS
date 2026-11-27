import type { InertiaFormProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { FormSection } from './form-section';
import type { KostForm, Rule } from './types';

export function RulesSection({
    form,
    rules,
}: {
    form: InertiaFormProps<KostForm>;
    rules: Rule[];
}) {
    function toggle(id: string, checked: boolean): void {
        const current = form.data.rules;

        form.setData(
            'rules',
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    }

    return (
        <FormSection
            title="Peraturan kost"
            description="Aturan yang berlaku untuk seluruh penghuni."
        >
            {rules.length === 0 ? (
                <p className="text-sm leading-[22px] text-ink-soft">
                    Belum ada peraturan pada master data.
                </p>
            ) : (
                <div className="grid gap-2">
                    {rules.map((rule) => {
                        const checked = form.data.rules.includes(rule.id);

                        return (
                            <label
                                key={rule.id}
                                className="flex cursor-pointer items-center gap-3 rounded-lg border border-hairline bg-surface p-3 has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas"
                            >
                                <Checkbox
                                    checked={checked}
                                    onCheckedChange={(value) =>
                                        toggle(rule.id, value === true)
                                    }
                                />
                                <span className="text-sm text-ink">
                                    {rule.name}
                                </span>
                            </label>
                        );
                    })}
                </div>
            )}
            <InputError message={form.errors.rules} />
        </FormSection>
    );
}
