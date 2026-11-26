import { Form, Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

type Props = {
    status?: string;
    email?: string | null;
};

export default function VerifyEmail({ status, email }: Props) {
    return (
        <>
            <Head title="Verifikasi email" />

            {status === 'verification-link-sent' && (
                <div
                    role="status"
                    className="mb-5 rounded-lg border border-hairline bg-ember-wash px-3 py-2 text-sm leading-[1.6] text-ink"
                >
                    Tautan verifikasi baru sudah dikirim ke alamat email yang
                    Anda daftarkan.
                </div>
            )}

            <div className="mb-6 rounded-lg border border-hairline bg-canvas px-4 py-3 text-sm leading-[1.6] text-ink-soft">
                Tautan verifikasi dikirim ke{' '}
                <span className="font-semibold text-ink">
                    {email ?? 'email terdaftar'}
                </span>
                . Buka email Anda dan klik tautan tersebut untuk melanjutkan.
            </div>

            <Form {...send.form()}>
                {({ processing }) => (
                    <Button
                        type="submit"
                        disabled={processing}
                        className="h-11 w-full rounded-lg bg-ember text-base font-semibold text-ink shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                    >
                        {processing && <Spinner />}
                        Kirim ulang email verifikasi
                    </Button>
                )}
            </Form>

            <div className="mt-6 border-t border-hairline pt-5">
                <Form {...logout.form()}>
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="secondary"
                            disabled={processing}
                            className="h-11 w-full rounded-lg border border-field bg-surface text-base font-semibold text-ink shadow-none hover:bg-canvas focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                        >
                            {processing && <Spinner />}
                            Keluar
                        </Button>
                    )}
                </Form>
            </div>
        </>
    );
}

function VerifyEmailLayout({ children }: { children: ReactNode }) {
    return (
        <AuthLoginLayout
            title="Verifikasi email"
            description="Buka tautan verifikasi yang baru kami kirim ke email Anda untuk mengaktifkan akun."
        >
            {children}
        </AuthLoginLayout>
    );
}

VerifyEmail.layout = VerifyEmailLayout;
