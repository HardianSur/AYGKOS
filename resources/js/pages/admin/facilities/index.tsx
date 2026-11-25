import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import FacilityController from '@/actions/App/Http/Controllers/Admin/FacilityController';
import { FacilityIcon } from '@/components/facility-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as facilitiesIndex } from '@/routes/admin/facilities';

type Scope = 'umum' | 'kamar';

type Facility = {
    id: string;
    name: string;
    scope: Scope;
    icon: string;
    kost_count: number;
    room_type_count: number;
};

type FacilityForm = {
    name: string;
    scope: Scope;
    icon: string;
};

const scopeLabels: Record<Scope, string> = {
    umum: 'Umum',
    kamar: 'Kamar',
};

function iconLabel(icon: string): string {
    return icon.replace(/-/g, ' ');
}

function usageLabel(facility: Facility): string {
    const parts: string[] = [];

    if (facility.kost_count > 0) {
        parts.push(`${facility.kost_count} kost`);
    }

    if (facility.room_type_count > 0) {
        parts.push(`${facility.room_type_count} tipe kamar`);
    }

    return parts.length > 0 ? parts.join(', ') : 'Tidak dipakai';
}

export default function FacilityIndex({
    facilities,
    iconNames,
}: {
    facilities: Facility[];
    iconNames: string[];
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Facility | null>(null);
    const [deleting, setDeleting] = useState<Facility | null>(null);

    const form = useForm<FacilityForm>({
        name: '',
        scope: 'umum',
        icon: iconNames[0] ?? 'building-2',
    });

    const deleteForm = useForm({ facility: '' });

    const used =
        editing !== null && editing.kost_count + editing.room_type_count > 0;

    function openCreate(): void {
        setEditing(null);
        form.clearErrors();
        form.setData({
            name: '',
            scope: 'umum',
            icon: iconNames[0] ?? 'building-2',
        });
        setDialogOpen(true);
    }

    function openEdit(facility: Facility): void {
        setEditing(facility);
        form.clearErrors();
        form.setData({
            name: facility.name,
            scope: facility.scope,
            icon: facility.icon,
        });
        setDialogOpen(true);
    }

    function submit(event: React.FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        };

        if (editing !== null) {
            form.put(FacilityController.update.url(editing.id), options);

            return;
        }

        form.post(FacilityController.store.url(), options);
    }

    function askDelete(facility: Facility): void {
        deleteForm.clearErrors();
        deleteForm.setData('facility', facility.id);
        setDeleting(facility);
    }

    function confirmDelete(): void {
        if (deleting === null) {
            return;
        }

        deleteForm.delete(FacilityController.destroy.url(deleting.id), {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
        });
    }

    return (
        <>
            <Head title="Master Fasilitas" />

            <div className="flex flex-1 flex-col gap-6 p-4 font-brand md:p-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl leading-[30px] font-bold text-ink">
                            Master Fasilitas
                        </h1>
                        <p className="max-w-[70ch] text-sm leading-[22px] text-ink-soft">
                            Daftar fasilitas yang boleh dipilih Pemilik Kost
                            pada kost dan tipe kamar.
                        </p>
                    </div>

                    <Button
                        type="button"
                        onClick={openCreate}
                        className="h-10 rounded-lg bg-ember font-medium text-[#1B2038] shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:text-[#12152A]"
                    >
                        <Plus className="size-4" />
                        Tambah fasilitas
                    </Button>
                </header>

                <div className="overflow-hidden rounded-xl border border-hairline bg-surface">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[40rem] text-left text-sm">
                            <thead className="border-b border-hairline bg-canvas text-ink-soft">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Nama
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Lingkup
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Pemakai
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 text-right font-medium"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {facilities.map((facility) => {
                                    const inUse =
                                        facility.kost_count +
                                            facility.room_type_count >
                                        0;

                                    return (
                                        <tr
                                            key={facility.id}
                                            className="border-b border-hairline last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-ember-wash text-ember-ink">
                                                        <FacilityIcon
                                                            name={facility.icon}
                                                            className="size-5"
                                                        />
                                                    </span>
                                                    <span className="font-bold text-ink">
                                                        {facility.name}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={
                                                        facility.scope ===
                                                        'umum'
                                                            ? 'inline-flex rounded-full bg-ember-wash px-2.5 py-0.5 text-xs font-bold text-ember-ink'
                                                            : 'inline-flex rounded-full bg-[#EDEFF5] px-2.5 py-0.5 text-xs font-bold text-ink-soft dark:bg-[#2A3050]'
                                                    }
                                                >
                                                    {
                                                        scopeLabels[
                                                            facility.scope
                                                        ]
                                                    }
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-ink-soft tabular-nums">
                                                {usageLabel(facility)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Ubah ${facility.name}`}
                                                        onClick={() =>
                                                            openEdit(facility)
                                                        }
                                                        className="text-ember-ink hover:bg-ember-wash hover:text-ember-ink focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Hapus ${facility.name}`}
                                                        title={
                                                            inUse
                                                                ? 'Fasilitas masih dipakai kost atau tipe kamar.'
                                                                : undefined
                                                        }
                                                        disabled={inUse}
                                                        onClick={() =>
                                                            askDelete(facility)
                                                        }
                                                        className="text-ink-soft hover:bg-[#FDECEA] hover:text-[#B42318] focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:hover:bg-[#3D1E24] dark:hover:text-[#FF8A80]"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {facilities.length === 0 && (
                        <p className="border-t border-hairline px-4 py-10 text-center text-sm text-ink-soft">
                            Belum ada fasilitas. Tambahkan fasilitas pertama
                            agar Pemilik Kost dapat memilihnya.
                        </p>
                    )}
                </div>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="border-hairline bg-surface text-ink shadow-[0_4px_16px_rgb(18_21_42/0.12)]">
                    <DialogHeader>
                        <DialogTitle className="text-lg leading-6 font-bold">
                            {editing === null
                                ? 'Tambah fasilitas'
                                : 'Ubah fasilitas'}
                        </DialogTitle>
                        <DialogDescription className="text-sm leading-[22px] text-ink-soft">
                            {editing === null
                                ? 'Fasilitas baru langsung tersedia untuk dipilih Pemilik Kost.'
                                : 'Perubahan nama dan ikon berlaku pada semua kost dan tipe kamar yang memakainya.'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="facility-name" className="text-ink">
                                Nama fasilitas
                            </Label>
                            <Input
                                id="facility-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                required
                                maxLength={100}
                                autoComplete="off"
                                placeholder="Contoh: Ruang Tamu"
                                aria-invalid={Boolean(form.errors.name)}
                                className="h-10 rounded-lg border-field bg-surface text-ink placeholder:text-ink-soft focus-visible:border-field focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="facility-scope"
                                className="text-ink"
                            >
                                Ruang lingkup
                            </Label>
                            <Select
                                value={form.data.scope}
                                onValueChange={(value) =>
                                    form.setData('scope', value as Scope)
                                }
                                disabled={used}
                            >
                                <SelectTrigger
                                    id="facility-scope"
                                    aria-invalid={Boolean(form.errors.scope)}
                                    className="h-10 w-full rounded-lg border-field bg-surface text-ink focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="border-hairline bg-surface text-ink">
                                    <SelectItem
                                        value="umum"
                                        className="focus:bg-ember-wash focus:text-ember-ink"
                                    >
                                        Umum
                                    </SelectItem>
                                    <SelectItem
                                        value="kamar"
                                        className="focus:bg-ember-wash focus:text-ember-ink"
                                    >
                                        Kamar
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs leading-4 text-ink-soft">
                                {used
                                    ? 'Ruang lingkup terkunci karena fasilitas masih dipakai.'
                                    : 'Umum dipakai seluruh kost, kamar khusus tipe kamar.'}
                            </p>
                            <InputError message={form.errors.scope} />
                        </div>

                        <div className="grid gap-2">
                            <span className="text-sm font-medium text-ink">
                                Ikon
                            </span>
                            <div
                                role="group"
                                aria-label="Pilih ikon fasilitas"
                                className="grid max-h-64 grid-cols-3 gap-2 overflow-y-auto rounded-lg border border-hairline p-2 sm:grid-cols-5"
                            >
                                {iconNames.map((icon) => {
                                    const selected = form.data.icon === icon;

                                    return (
                                        <button
                                            key={icon}
                                            type="button"
                                            aria-pressed={selected}
                                            onClick={() =>
                                                form.setData('icon', icon)
                                            }
                                            className={
                                                selected
                                                    ? 'flex flex-col items-center gap-1 rounded-lg border border-ember bg-ember-wash px-2 py-2 text-xs leading-4 font-medium text-ember-ink capitalize focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none'
                                                    : 'flex flex-col items-center gap-1 rounded-lg border border-transparent px-2 py-2 text-xs leading-4 font-medium text-ink-soft capitalize hover:border-hairline hover:bg-canvas focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none'
                                            }
                                        >
                                            <FacilityIcon
                                                name={icon}
                                                className="size-5"
                                            />
                                            {iconLabel(icon)}
                                        </button>
                                    );
                                })}
                            </div>
                            <InputError message={form.errors.icon} />
                        </div>

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-ember font-medium text-[#1B2038] shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:text-[#12152A]"
                            >
                                {form.processing ? 'Menyimpan…' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
            >
                <DialogContent className="border-hairline bg-surface text-ink shadow-[0_4px_16px_rgb(18_21_42/0.12)]">
                    <DialogHeader>
                        <DialogTitle className="text-lg leading-6 font-bold">
                            Hapus fasilitas?
                        </DialogTitle>
                        <DialogDescription className="text-sm leading-[22px] text-ink-soft">
                            Fasilitas “{deleting?.name}” akan dihapus permanen.
                            Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>

                    <InputError message={deleteForm.errors.facility} />

                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setDeleting(null)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={deleteForm.processing}
                            onClick={confirmDelete}
                        >
                            {deleteForm.processing ? 'Menghapus…' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

FacilityIndex.layout = {
    breadcrumbs: [
        {
            title: 'Fasilitas',
            href: facilitiesIndex.url(),
        },
    ],
};
