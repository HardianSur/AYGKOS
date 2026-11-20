import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';
import { send, store } from '@/routes/verification/whatsapp';

type Props = {
    status?: string;
    whatsappNumber?: string | null;
    canResend: boolean;
    resendIn: number;
};

const fieldClass =
    'h-12 rounded-lg border-field bg-surface text-center text-xl tracking-[0.4em] text-ink shadow-none placeholder:text-ink-soft/60 placeholder:tracking-[0.2em] focus-visible:border-ember-ink focus-visible:ring-2 focus-visible:ring-ember-ink/35';

function formatWait(seconds: number): string {
    if (seconds < 60) {
        return `${seconds} detik`;
    }

    return `${Math.ceil(seconds / 60)} menit`;
}

export default function VerifyWhatsapp({
    status,
    whatsappNumber,
    canResend,
    resendIn,
}: Props) {
    const codeForm = useForm({ code: '' });
    const resendForm = useForm({});
    const { errors } = usePage<{
        errors: Record<string, string | undefined>;
    }>().props;
    const [remaining, setRemaining] = useState(resendIn);

    useEffect(() => {
        setRemaining(resendIn);

        if (resendIn <= 0) {
            return;
        }

        const timer = window.setInterval(() => {
            setRemaining((value) => (value > 0 ? value - 1 : 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, [resendIn]);

    const submitCode = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        codeForm.post(store.url(), { preserveScroll: true });
    };

    const requestNewCode = () => {
        resendForm.post(send.url(), { preserveScroll: true });
    };

    const resendBlocked = !canResend || remaining > 0;

    return (
        <>
            <Head title="Verifikasi WhatsApp" />

            {status && (
                <div
                    role="status"
                    className="mb-5 rounded-lg border border-hairline bg-ember-wash px-3 py-2 text-sm leading-[1.6] text-ink"
                >
                    {status}
                </div>
            )}

            <div className="mb-6 rounded-lg border border-hairline bg-canvas px-4 py-3 text-sm leading-[1.6] text-ink-soft">
                Kode 6 digit dikirim ke WhatsApp{' '}
                <span className="font-semibold text-ink">
                    {whatsappNumber ?? 'nomor terdaftar'}
                </span>
                . Kode berlaku 5 menit.
            </div>

            <form onSubmit={submitCode} className="flex flex-col gap-5">
                <div className="grid gap-2">
                    <Label htmlFor="code" className="text-ink">
                        Kode verifikasi
                    </Label>
                    <Input
                        id="code"
                        name="code"
                        type="text"
                        required
                        autoFocus
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxLength={6}
                        placeholder="000000"
                        value={codeForm.data.code}
                        onChange={(event) =>
                            codeForm.setData(
                                'code',
                                event.target.value
                                    .replace(/\D/g, '')
                                    .slice(0, 6),
                            )
                        }
                        aria-invalid={!!codeForm.errors.code}
                        aria-describedby={
                            codeForm.errors.code ? 'code-error' : undefined
                        }
                        className={fieldClass}
                    />
                    <InputError
                        id="code-error"
                        role="alert"
                        message={codeForm.errors.code}
                    />
                </div>

                <Button
                    type="submit"
                    disabled={
                        codeForm.processing || codeForm.data.code.length !== 6
                    }
                    className="h-11 w-full rounded-lg bg-ember text-base font-semibold text-ink shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                >
                    {codeForm.processing && <Spinner />}
                    Verifikasi nomor
                </Button>
            </form>

            <div className="mt-6 border-t border-hairline pt-5">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={requestNewCode}
                    disabled={resendBlocked || resendForm.processing}
                    className="h-11 w-full rounded-lg border border-field bg-surface text-base font-semibold text-ink shadow-none hover:bg-canvas focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                >
                    {resendForm.processing && <Spinner />}
                    {remaining > 0
                        ? `Kirim ulang dalam ${formatWait(remaining)}`
                        : canResend
                          ? 'Kirim ulang kode'
                          : 'Batas kirim ulang tercapai'}
                </Button>

                <InputError
                    id="resend-error"
                    role="alert"
                    message={errors.resend}
                    className="mt-2 text-center"
                />
            </div>
        </>
    );
}

function VerifyWhatsappLayout({ children }: { children: ReactNode }) {
    return (
        <AuthLoginLayout
            title="Verifikasi nomor WhatsApp"
            description="Masukkan kode yang kami kirim untuk mengaktifkan akun pemilik kost."
        >
            {children}
        </AuthLoginLayout>
    );
}

VerifyWhatsapp.layout = VerifyWhatsappLayout;
