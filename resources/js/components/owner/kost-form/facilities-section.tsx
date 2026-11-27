import type { InertiaFormProps } from '@inertiajs/react';
import { FacilityIcon } from '@/components/facility-icon';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { FormSection } from './form-section';
import type { Facility, KostForm } from './types';

export function FacilitiesSection({
    form,
    facilities,
}: {
    form: InertiaFormProps<KostForm>;
    facilities: Facility[];
}) {
    function toggle(id: string, checked: boolean): void {
        const current = form.data.facilities;

        form.setData(
            'facilities',
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    }

    return (
        <FormSection
            title="Fasilitas umum"
            description="Pilih fasilitas yang tersedia untuk seluruh penghuni kost."
        >
            {facilities.length === 0 ? (
                <p className="text-sm leading-[22px] text-ink-soft">
                    Belum ada fasilitas umum pada master data.
                </p>
            ) : (
                <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    {facilities.map((facility) => {
                        const checked = form.data.facilities.includes(
                            facility.id,
                        );

                        return (
                            <label
                                key={facility.id}
                                className="flex cursor-pointer items-center gap-3 rounded-lg border border-hairline bg-surface p-3 has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-canvas"
                            >
                                <Checkbox
                                    checked={checked}
                                    onCheckedChange={(value) =>
                                        toggle(facility.id, value === true)
                                    }
                                />
                                <span className="flex items-center gap-2 text-sm font-medium text-ink">
                                    <FacilityIcon
                                        name={facility.icon}
                                        className="size-4 text-ember-ink"
                                    />
                                    {facility.name}
                                </span>
                            </label>
                        );
                    })}
                </div>
            )}
            <InputError message={form.errors.facilities} />
        </FormSection>
    );
}
