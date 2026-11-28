import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
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
    returnFocusRef?: React.RefObject<HTMLButtonElement | null>;
};

export function LogoutDialog({ open, onOpenChange, returnFocusRef }: Props) {
    const [processing, setProcessing] = useState(false);
    const cleanup = useMobileNavigation();
    const loggingOut = useRef(false);

    const handleLogout = () => {
        if (processing) {
            return;
        }

        setProcessing(true);
        cleanup();
        router.flushAll();
        loggingOut.current = true;
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

    const handleOpenChange = (next: boolean) => {
        if (next) {
            loggingOut.current = false;
        }
        onOpenChange(next);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent
                className="rounded-xl border-hairline bg-surface text-ink shadow-floating"
                overlayClassName="bg-scrim"
                showCloseButton={false}
                onCloseAutoFocus={(event) => {
                    if (loggingOut.current || !returnFocusRef?.current) {
                        return;
                    }
                    event.preventDefault();
                    returnFocusRef.current.focus();
                }}
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
                            className="rounded-lg border border-hairline bg-surface! text-ink! hover:border-field hover:bg-canvas! focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        className="rounded-lg bg-danger! text-danger-ink! shadow-none! hover:bg-danger-ink! hover:text-danger! focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
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
