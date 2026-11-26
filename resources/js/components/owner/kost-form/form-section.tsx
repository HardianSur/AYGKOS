import type { ReactNode } from 'react';

export function FormSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="space-y-5 rounded-xl border border-hairline bg-surface p-5 sm:p-6">
            <header className="space-y-1">
                <h2 className="text-lg leading-6 font-bold text-ink">
                    {title}
                </h2>
                <p className="max-w-[70ch] text-sm leading-[22px] text-ink-soft">
                    {description}
                </p>
            </header>
            {children}
        </section>
    );
}
