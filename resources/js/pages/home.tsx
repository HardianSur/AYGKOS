import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Home() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Beranda" />
            <main className="flex min-h-screen flex-col items-center justify-center gap-6 p-6">
                <h1 className="text-2xl font-bold text-ink">
                    Hai, {auth.user.name}
                </h1>

                <Button variant="outline" asChild>
                    <Link href={logout()} method="post" as="button">
                        Keluar
                    </Link>
                </Button>
            </main>
        </>
    );
}
