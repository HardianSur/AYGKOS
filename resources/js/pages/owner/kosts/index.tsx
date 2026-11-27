import { Head, Link } from '@inertiajs/react';
import { Building2, ImageOff, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { create, index } from '@/routes/owner/kosts';

type KostStatus =
    | 'menunggu_verifikasi'
    | 'disetujui'
    | 'ditolak'
    | 'ditangguhkan'
    | 'nonaktif';

type Kost = {
    id: string;
    name: string;
    city: string;
    type: 'putra' | 'putri' | 'campur';
    status: KostStatus;
    status_reason: string | null;
    photo_url: string | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    first_page_url: string;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const statusStyles: Record<KostStatus, string> = {
    menunggu_verifikasi:
        'bg-[#E8F0FB] text-[#1D4F91] dark:bg-[#1B2B4F] dark:text-[#7DB2FF]',
    disetujui:
        'bg-[#E6F4EC] text-[#0C7A45] dark:bg-[#14352A] dark:text-[#5BD68F]',
    ditolak:
        'bg-[#FDECEA] text-[#B42318] dark:bg-[#3D1E24] dark:text-[#FF8A80]',
    ditangguhkan:
        'bg-[#FDECEA] text-[#B42318] dark:bg-[#3D1E24] dark:text-[#FF8A80]',
    nonaktif:
        'bg-[#EDEFF5] text-[#5E6580] dark:bg-[#2A3050] dark:text-[#A4ABC8]',
};

const statusLabels: Record<KostStatus, string> = {
    menunggu_verifikasi: 'Menunggu Verifikasi',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    ditangguhkan: 'Ditangguhkan',
    nonaktif: 'Nonaktif',
};

const typeLabels: Record<Kost['type'], string> = {
    putra: 'Putra',
    putri: 'Putri',
    campur: 'Campur',
};

export default function OwnerKostIndex({ kosts }: { kosts: Paginated<Kost> }) {
    return (
        <>
            <Head title="Kost Saya" />

            <div className="flex flex-1 flex-col gap-6 p-4 font-brand md:p-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl leading-[30px] font-bold text-ink">
                            Kost Saya
                        </h1>
                        <p className="max-w-[70ch] text-sm leading-[22px] text-ink-soft">
                            Kelola kost yang Anda daftarkan dan pantau status
                            verifikasinya.
                        </p>
                    </div>

                    <Button
                        asChild
                        className="h-10 rounded-lg bg-ember font-medium text-[#1B2038] shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:text-[#12152A]"
                    >
                        <Link href={create()}>
                            <Plus className="size-4" />
                            Ajukan kost
                        </Link>
                    </Button>
                </header>

                {kosts.total === 0 ? (
                    <EmptyState />
                ) : kosts.data.length === 0 ? (
                    <>
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed border-field bg-surface px-6 py-10 text-center">
                            <p className="text-sm leading-[22px] text-ink-soft">
                                Halaman ini tidak berisi kost.
                            </p>
                            <Button variant="secondary" asChild>
                                <Link href={kosts.first_page_url}>
                                    Kembali ke halaman pertama
                                </Link>
                            </Button>
                        </div>

                        <Pagination kosts={kosts} />
                    </>
                ) : (
                    <>
                        <ul className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {kosts.data.map((kost) => (
                                <li key={kost.id}>
                                    <KostCard kost={kost} />
                                </li>
                            ))}
                        </ul>

                        <Pagination kosts={kosts} />
                    </>
                )}
            </div>
        </>
    );
}

function KostCard({ kost }: { kost: Kost }) {
    const showReason =
        kost.status_reason !== null &&
        (kost.status === 'ditolak' || kost.status === 'ditangguhkan');

    return (
        <article className="flex h-full flex-col overflow-hidden rounded-lg border border-hairline bg-surface">
            {kost.photo_url === null ? (
                <div className="flex aspect-video items-center justify-center bg-canvas text-ink-soft">
                    <div className="flex flex-col items-center gap-2">
                        <ImageOff className="size-8" aria-hidden="true" />
                        <span className="text-xs leading-4">Tanpa foto</span>
                    </div>
                </div>
            ) : (
                <img
                    src={kost.photo_url}
                    alt={`Foto ${kost.name}`}
                    loading="lazy"
                    className="aspect-video w-full object-cover"
                />
            )}

            <div className="flex flex-1 flex-col gap-3 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <h2 className="text-lg leading-6 font-bold text-ink">
                        {kost.name}
                    </h2>
                    <span
                        className={cn(
                            'inline-flex rounded-full px-2.5 py-0.5 text-xs leading-4 font-bold',
                            statusStyles[kost.status],
                        )}
                    >
                        {statusLabels[kost.status]}
                    </span>
                </div>

                <dl className="flex flex-wrap gap-x-4 gap-y-1 text-sm leading-[22px] text-ink-soft">
                    <div className="flex items-center gap-1">
                        <dt className="sr-only">Kota</dt>
                        <dd>{kost.city}</dd>
                    </div>
                    <div className="flex items-center gap-1">
                        <dt className="sr-only">Tipe kost</dt>
                        <dd>Kost {typeLabels[kost.type]}</dd>
                    </div>
                </dl>

                {showReason && (
                    <p className="rounded-md bg-[#FDECEA] px-3 py-2 text-sm leading-[22px] text-[#B42318] dark:bg-[#3D1E24] dark:text-[#FF8A80]">
                        <span className="font-bold">Alasan: </span>
                        {kost.status_reason}
                    </p>
                )}
            </div>
        </article>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed border-field bg-surface px-6 py-14 text-center">
            <span className="grid size-14 place-items-center rounded-full bg-ember-wash text-ember-ink">
                <Building2 className="size-7" aria-hidden="true" />
            </span>
            <div className="space-y-1">
                <h2 className="text-lg leading-6 font-bold text-ink">
                    Belum ada kost
                </h2>
                <p className="max-w-[46ch] text-sm leading-[22px] text-ink-soft">
                    Ajukan kost pertama Anda. Setelah dikirim, kost akan
                    menunggu verifikasi Admin sebelum tampil kepada pencari
                    kost.
                </p>
            </div>
            <Button
                asChild
                className="h-10 rounded-lg bg-ember font-medium text-[#1B2038] shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas dark:text-[#12152A]"
            >
                <Link href={create()}>
                    <Plus className="size-4" />
                    Ajukan kost
                </Link>
            </Button>
        </div>
    );
}

function Pagination({ kosts }: { kosts: Paginated<Kost> }) {
    if (kosts.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Navigasi halaman kost"
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p className="text-sm leading-[22px] text-ink-soft">
                Halaman {kosts.current_page} dari {kosts.last_page}
                <span className="sr-only">, {kosts.total} kost</span>
            </p>

            <div className="flex gap-2">
                {kosts.prev_page_url === null ? (
                    <Button variant="secondary" disabled>
                        Sebelumnya
                    </Button>
                ) : (
                    <Button variant="secondary" asChild>
                        <Link href={kosts.prev_page_url} preserveScroll>
                            Sebelumnya
                        </Link>
                    </Button>
                )}

                {kosts.next_page_url === null ? (
                    <Button variant="secondary" disabled>
                        Berikutnya
                    </Button>
                ) : (
                    <Button variant="secondary" asChild>
                        <Link href={kosts.next_page_url} preserveScroll>
                            Berikutnya
                        </Link>
                    </Button>
                )}
            </div>
        </nav>
    );
}

OwnerKostIndex.layout = {
    breadcrumbs: [
        {
            title: 'Kost Saya',
            href: index.url(),
        },
    ],
};
