import type { LucideIcon } from 'lucide-react';
import {
    Facebook,
    Globe,
    Instagram,
    Linkedin,
    Send,
    Youtube,
} from 'lucide-react';

/**
 * TikTok's note mark; lucide has no TikTok icon.
 */
export function TikTok({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            className={className}
            aria-hidden="true"
        >
            <path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5 2.59 2.59 0 0 1-2.59-2.59 2.59 2.59 0 0 1 3.3-2.49V9.66a5.68 5.68 0 0 0-6.4 5.65A5.68 5.68 0 0 0 9.86 21a5.68 5.68 0 0 0 5.68-5.68V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.28 4.28 0 0 1-3.24-1.48Z" />
        </svg>
    );
}

/**
 * The label and icon for every social link Vendly shows, on the website
 * footer and on stores.
 */
export const socialIcons: Record<
    string,
    { label: string; icon: LucideIcon | typeof TikTok }
> = {
    facebook: { label: 'Facebook', icon: Facebook },
    instagram: { label: 'Instagram', icon: Instagram },
    tiktok: { label: 'TikTok', icon: TikTok },
    youtube: { label: 'YouTube', icon: Youtube },
    telegram: { label: 'Telegram', icon: Send },
    linkedin: { label: 'LinkedIn', icon: Linkedin },
    website: { label: 'Website', icon: Globe },
};
