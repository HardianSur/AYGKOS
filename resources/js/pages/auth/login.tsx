import { Form, Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

const fieldClass =
    'h-11 rounded-lg border-field bg-surface px-3 text-base text-ink shadow-none placeholder:text-ink-soft focus-visible:border-ember-ink focus-visible:ring-2 focus-visible:ring-ember-ink/35';

const linkClass =
    'rounded-sm font-semibold text-ink underline decoration-ember decoration-2 underline-offset-4 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface focus-visible:outline-none';

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            {status && (
                <div
                    role="status"
                    className="mb-5 rounded-lg border border-hairline bg-ember-wash px-3 py-2 text-sm leading-[1.6] text-ink"
                >
                    {status}
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email" className="text-ink">
                                Email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="nama@email.com"
                                aria-invalid={!!errors.email}
                                aria-describedby={
                                    errors.email ? 'email-error' : undefined
                                }
                                className={fieldClass}
                            />
                            <InputError
                                id="email-error"
                                role="alert"
                                message={errors.email}
                            />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-baseline justify-between gap-3">
                                <Label htmlFor="password" className="text-ink">
                                    Kata sandi
                                </Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className={linkClass}
                                        tabIndex={5}
                                    >
                                        Lupa sandi?
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Kata sandi Anda"
                                showLabel="Tampilkan kata sandi"
                                hideLabel="Sembunyikan kata sandi"
                                aria-invalid={!!errors.password}
                                aria-describedby={
                                    errors.password
                                        ? 'password-error'
                                        : undefined
                                }
                                className={fieldClass}
                            />
                            <InputError
                                id="password-error"
                                role="alert"
                                message={errors.password}
                            />
                        </div>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                value="on"
                                tabIndex={3}
                                className="border-field focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface data-[state=checked]:border-ember data-[state=checked]:bg-ember data-[state=checked]:text-ink"
                            />
                            <Label
                                htmlFor="remember"
                                className="text-sm text-ink-soft"
                            >
                                Ingat saya
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                            className="mt-1 h-11 w-full rounded-lg bg-ember text-base font-semibold text-ink shadow-none hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                        >
                            {processing && <Spinner />}
                            Masuk
                        </Button>

                        <p className="border-t border-hairline pt-5 text-center text-sm leading-[1.6] text-ink-soft">
                            Belum punya akun?{' '}
                            <TextLink
                                href={register()}
                                className={linkClass}
                                tabIndex={6}
                            >
                                Daftar Sekarang!
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

function LoginLayout({ children }: { children: ReactNode }) {
    return (
        <AuthLoginLayout
            title="Masuk ke akun Anda"
            description="Gunakan email dan kata sandi yang Anda daftarkan."
        >
            {children}
        </AuthLoginLayout>
    );
}

Login.layout = LoginLayout;
