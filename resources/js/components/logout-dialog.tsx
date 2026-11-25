import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function LogoutDialog({ open, onOpenChange }: Props) {
    const [processing, setProcessing] = useState(false);
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        if (processing) {
            return;
        }

        setProcessing(true);
        cleanup();
        router.flushAll();
        router.post(
            logout.url(),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    onOpenChange(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className="rounded-xl border-hairline bg-surface text-ink shadow-[0_4px_16px_rgb(18_21_42/0.12)]"
                overlayClassName="bg-night/60"
                showCloseButton={false}
            >
                <DialogHeader className="text-left">
                    <DialogTitle className="text-lg leading-6 font-bold">
                        Keluar dari akun?
                    </DialogTitle>
                    <DialogDescription className="text-sm text-ink-soft">
                        Sesi Anda akan berakhir dan Anda akan kembali ke halaman
                        masuk.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button
                            type="button"
                            variant="secondary"
                            className="rounded-lg border border-hairline focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        className="rounded-lg bg-danger text-danger-ink hover:bg-danger/90 focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                        disabled={processing}
                        onClick={handleLogout}
                        data-test="confirm-logout-button"
                    >
                        Keluar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
