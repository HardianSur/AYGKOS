import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { BasicSection } from '@/components/owner/kost-form/basic-section';
import { DocumentsSection } from '@/components/owner/kost-form/documents-section';
import { FacilitiesSection } from '@/components/owner/kost-form/facilities-section';
import { LocationSection } from '@/components/owner/kost-form/location-section';
import { PhotosSection } from '@/components/owner/kost-form/photos-section';
import { RulesSection } from '@/components/owner/kost-form/rules-section';
import {
    emptyDocuments,
    type Facility,
    type KostForm,
    type Rule,
} from '@/components/owner/kost-form/types';
import { Button } from '@/components/ui/button';
import { create, index, store } from '@/routes/owner/kosts';

export default function OwnerKostCreate({
    facilities,
    rules,
    geoapifyKey,
}: {
    facilities: Facility[];
    rules: Rule[];
    geoapifyKey: string | null;
}) {
    const form = useForm<KostForm>({
        name: '',
        address: '',
        city: '',
        latitude: '',
        longitude: '',
        type: 'campur',
        description: '',
        facilities: [],
        rules: [],
        photos: [],
        documents: emptyDocuments,
    });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.post(store.url(), { forceFormData: true });
    }

    return (
        <>
            <Head title="Ajukan Kost" />

            <div className="flex flex-1 flex-col gap-6 p-4 font-brand md:p-6">
                <header className="space-y-2">
                    <Link
                        href={index()}
                        className="inline-flex items-center gap-1.5 rounded-sm text-sm leading-[22px] font-medium text-ember-ink hover:underline focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Kembali ke Kost Saya
                    </Link>
                    <h1 className="text-2xl leading-[30px] font-bold text-ink">
                        Ajukan Kost
                    </h1>
                    <p className="max-w-[70ch] text-sm leading-[22px] text-ink-soft">
                        Lengkapi data kost, tandai titik lokasinya pada peta,
                        lalu unggah foto dan dokumen izin. Pengajuan akan
                        menunggu verifikasi Admin sebelum tampil kepada pencari
                        kost.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-6">
                    <BasicSection form={form} />
                    <LocationSection form={form} geoapifyKey={geoapifyKey} />
                    <FacilitiesSection form={form} facilities={facilities} />
                    <RulesSection form={form} rules={rules} />
                    <PhotosSection form={form} />
                    <DocumentsSection form={form} />

                    <div className="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:justify-end">
                        <Button
                            type="button"
                            variant="secondary"
                            asChild
                            className="h-10 rounded-lg"
                        >
                            <Link href={index()}>Batal</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-10 rounded-lg bg-ember font-medium text-[#1B2038] shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:text-[#12152A]"
                        >
                            <Upload className="size-4" />
                            {form.processing
                                ? 'Mengirim pengajuan…'
                                : 'Kirim pengajuan'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

OwnerKostCreate.layout = {
    breadcrumbs: [
        {
            title: 'Kost Saya',
            href: index.url(),
        },
        {
            title: 'Ajukan Kost',
            href: create.url(),
        },
    ],
};
