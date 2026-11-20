import { Form, Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLoginLayout from '@/layouts/auth/auth-login-layout';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

type Role = 'customer' | 'pemilik_kost';

const fieldClass =
    'h-11 rounded-lg border-field bg-surface px-3 text-base text-ink shadow-none placeholder:text-ink-soft focus-visible:border-ember-ink focus-visible:ring-2 focus-visible:ring-ember-ink/35';

const roleCardClass =
    'flex cursor-pointer flex-col gap-1.5 rounded-lg border border-field bg-surface p-4 transition-colors has-checked:border-ember-ink has-checked:bg-ember-wash has-focus-visible:ring-2 has-focus-visible:ring-ink has-focus-visible:ring-offset-2 has-focus-visible:ring-offset-surface';

const linkClass =
    'rounded-sm font-semibold text-ink underline decoration-ember decoration-2 underline-offset-4 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface focus-visible:outline-none';

const roles: { value: Role; label: string; hint: string }[] = [
    {
        value: 'customer',
        label: 'Pencari kos',
        hint: 'Saya ingin mencari dan menghubungi pemilik kost.',
    },
    {
        value: 'pemilik_kost',
        label: 'Pemilik kos',
        hint: 'Saya ingin mengiklankan kost yang saya miliki.',
    },
];

export default function Register({ passwordRules }: Props) {
    const [role, setRole] = useState<Role>('customer');
    const isOwner = role === 'pemilik_kost';

    return (
        <>
            <Head title="Daftar" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <fieldset className="grid gap-3">
                            <legend className="mb-1 text-sm font-medium text-ink">
                                Daftar sebagai
                            </legend>

                            <div className="grid gap-3 sm:grid-cols-2">
                                {roles.map((option) => (
                                    <label
                                        key={option.value}
                                        className={roleCardClass}
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            value={option.value}
                                            checked={role === option.value}
                                            onChange={() =>
                                                setRole(option.value)
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
                                ))}
                            </div>

                            <InputError message={errors.role} role="alert" />
                        </fieldset>

                        <div className="grid gap-2">
                            <Label htmlFor="name" className="text-ink">
                                Nama lengkap
                            </Label>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autoFocus
                                autoComplete="name"
                                placeholder="Nama sesuai identitas"
                                aria-invalid={!!errors.name}
                                aria-describedby={
                                    errors.name ? 'name-error' : undefined
                                }
                                className={fieldClass}
                            />
                            <InputError
                                id="name-error"
                                role="alert"
                                message={errors.name}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email" className="text-ink">
                                Email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
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

                        {isOwner && (
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="whatsapp_number"
                                    className="text-ink"
                                >
                                    Nomor WhatsApp
                                </Label>
                                <Input
                                    id="whatsapp_number"
                                    type="tel"
                                    name="whatsapp_number"
                                    required
                                    inputMode="tel"
                                    autoComplete="tel"
                                    placeholder="081234567890"
                                    aria-invalid={!!errors.whatsapp_number}
                                    aria-describedby={
                                        errors.whatsapp_number
                                            ? 'whatsapp_number-error'
                                            : 'whatsapp_number-hint'
                                    }
                                    className={fieldClass}
                                />
                                <p
                                    id="whatsapp_number-hint"
                                    className="text-xs leading-[1.5] text-ink-soft"
                                >
                                    Kode verifikasi akan dikirim ke nomor ini.
                                </p>
                                <InputError
                                    id="whatsapp_number-error"
                                    role="alert"
                                    message={errors.whatsapp_number}
                                />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="password" className="text-ink">
                                Kata sandi
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="new-password"
                                placeholder="Kata sandi Anda"
                                showLabel="Tampilkan kata sandi"
                                hideLabel="Sembunyikan kata sandi"
                                passwordrules={passwordRules}
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

                        <div className="grid gap-2">
                            <Label
                                htmlFor="password_confirmation"
                                className="text-ink"
                            >
                                Ulangi kata sandi
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                                placeholder="Ketik ulang kata sandi"
                                showLabel="Tampilkan kata sandi"
                                hideLabel="Sembunyikan kata sandi"
                                passwordrules={passwordRules}
                                aria-invalid={!!errors.password_confirmation}
                                aria-describedby={
                                    errors.password_confirmation
                                        ? 'password_confirmation-error'
                                        : undefined
                                }
                                className={fieldClass}
                            />
                            <InputError
                                id="password_confirmation-error"
                                role="alert"
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="register-user-button"
                            className={cn(
                                'mt-1 h-11 w-full rounded-lg bg-ember text-base font-semibold text-ink shadow-none',
                                'hover:bg-ember/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface',
                            )}
                        >
                            {processing && <Spinner />}
                            Buat akun
                        </Button>

                        <p className="border-t border-hairline pt-5 text-center text-sm leading-[1.6] text-ink-soft">
                            Sudah punya akun?{' '}
                            <TextLink href={login()} className={linkClass}>
                                Masuk
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

function RegisterLayout({ children }: { children: ReactNode }) {
    return (
        <AuthLoginLayout
            title="Buat akun AYGKOS"
            description="Pilih peran Anda, lalu isi data akun."
        >
            {children}
        </AuthLoginLayout>
    );
}

Register.layout = RegisterLayout;
