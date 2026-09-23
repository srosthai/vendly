import type { PropsWithChildren } from 'react';

/**
 * Settings is a single Profile page, so there is no side menu.
 */
export default function SettingsLayout({ children }: PropsWithChildren) {
    return (
        <div className="p-4 md:p-6">
            <div className="flex max-w-3xl flex-col gap-6">{children}</div>
        </div>
    );
}
