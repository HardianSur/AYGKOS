import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const sellingPoints = [
    'Pemilik kost diverifikasi sebelum iklannya tayang.',
    'Harga dan fasilitas ditulis apa adanya, tanpa biaya tersembunyi.',
    'Hubungi pemilik langsung lewat WhatsApp.',
];

export default function AuthLoginLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh bg-canvas font-brand text-ink lg:grid-cols-[1.1fr_1fr]">
            <aside className="relative hidden overflow-hidden bg-canvas px-14 py-12 text-ink lg:flex lg:flex-col lg:justify-between">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -right-40 -bottom-40 size-[34rem] rounded-full bg-[radial-gradient(circle,var(--color-ember),transparent_65%)] opacity-30 blur-2xl"
                />

                <Link
                    href={home()}
                    className="relative flex items-center gap-3 rounded-md focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-canvas focus-visible:outline-none"
                >
                    <AppLogoIcon className="size-8 fill-current text-ember" />
                    <span className="text-lg font-bold tracking-tight">
                        AYGKOS
                    </span>
                </Link>

                <div className="relative max-w-md">
                    <p className="text-[2.375rem] leading-[1.18] font-bold text-balance">
                        Cari kost dekat kampus, langsung dari pemiliknya.
                    </p>
                    <p className="mt-5 max-w-sm text-base leading-[1.6] text-ink-soft">
                        Tempat tinggal yang jelas sebelum Anda datang
                        melihatnya. Setiap iklan memuat lokasi, harga, dan
                        fasilitas yang sudah diperiksa.
                    </p>

                    <ul className="mt-10 max-w-sm divide-y divide-ink-soft/25 border-t border-ink-soft/25">
                        {sellingPoints.map((point) => (
                            <li
                                key={point}
                                className="py-3 text-sm leading-[1.6] text-ink-soft"
                            >
                                {point}
                            </li>
                        ))}
                    </ul>
                </div>
            </aside>

            <main className="flex items-center justify-center px-4 py-10 sm:px-8 lg:px-10 lg:py-16">
                <div className="w-full max-w-md rounded-xl border border-hairline bg-surface p-6 sm:p-8">
                    <div className="mb-8 space-y-2">
                        <h1 className="text-2xl leading-[1.25] font-bold">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm leading-[1.6] text-ink-soft">
                                {description}
                            </p>
                        )}
                    </div>

                    {children}
                </div>
            </main>
        </div>
    );
}
