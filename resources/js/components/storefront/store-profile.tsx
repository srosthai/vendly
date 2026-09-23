import { Clock, MapPin, Phone } from 'lucide-react';
import { socialIcons } from '@/components/social-icons';
import { cn } from '@/lib/utils';

export type StoreProfile = {
    accent: string | null;
    phone: string | null;
    address: string | null;
    hours: string | null;
    socials: Record<string, string>;
};

/**
 * Each accent a store can choose: the mark's fill and text, and the thin
 * band across the top of its header. Orange carries navy text, the rest
 * white, so every mark stays readable.
 */
export const accents: Record<
    string,
    { label: string; swatch: string; mark: string; band: string }
> = {
    blue: {
        label: 'Blue',
        swatch: 'bg-[#0054D5]',
        mark: 'bg-[#0054D5] text-white',
        band: 'from-[#0054D5] to-[#0054D5]/40',
    },
    orange: {
        label: 'Orange',
        swatch: 'bg-[#FD890F]',
        mark: 'bg-[#FD890F] text-[#081A3B]',
        band: 'from-[#FD890F] to-[#FD890F]/40',
    },
    green: {
        label: 'Green',
        swatch: 'bg-[#15803D]',
        mark: 'bg-[#15803D] text-white',
        band: 'from-[#15803D] to-[#15803D]/40',
    },
    teal: {
        label: 'Teal',
        swatch: 'bg-[#0F766E]',
        mark: 'bg-[#0F766E] text-white',
        band: 'from-[#0F766E] to-[#0F766E]/40',
    },
    purple: {
        label: 'Purple',
        swatch: 'bg-[#7C3AED]',
        mark: 'bg-[#7C3AED] text-white',
        band: 'from-[#7C3AED] to-[#7C3AED]/40',
    },
    pink: {
        label: 'Pink',
        swatch: 'bg-[#DB2777]',
        mark: 'bg-[#DB2777] text-white',
        band: 'from-[#DB2777] to-[#DB2777]/40',
    },
    red: {
        label: 'Red',
        swatch: 'bg-[#DC2626]',
        mark: 'bg-[#DC2626] text-white',
        band: 'from-[#DC2626] to-[#DC2626]/40',
    },
    slate: {
        label: 'Slate',
        swatch: 'bg-[#334155]',
        mark: 'bg-[#334155] text-white',
        band: 'from-[#334155] to-[#334155]/40',
    },
};

export function mapLink(address: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
}

/**
 * The store's contact details as tappable chips: call, the address on a
 * map, opening hours, and social links. Only filled details appear.
 */
export function StoreContact({
    profile,
    className,
}: {
    profile: StoreProfile;
    className?: string;
}) {
    const socials = Object.entries(profile.socials);

    if (
        !profile.phone &&
        !profile.address &&
        !profile.hours &&
        socials.length === 0
    ) {
        return null;
    }

    const chip =
        'inline-flex min-h-9 items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors';

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            {profile.phone ? (
                <a
                    href={`tel:${profile.phone.replace(/[^\d+]/g, '')}`}
                    className={cn(
                        chip,
                        'font-medium hover:border-primary/40 hover:text-primary',
                    )}
                >
                    <Phone className="size-3.5" aria-hidden="true" />
                    {profile.phone}
                </a>
            ) : null}
            {profile.address ? (
                <a
                    href={mapLink(profile.address)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={cn(
                        chip,
                        'text-muted-foreground hover:border-primary/40 hover:text-foreground',
                    )}
                >
                    <MapPin className="size-3.5" aria-hidden="true" />
                    {profile.address}
                </a>
            ) : null}
            {profile.hours ? (
                <span className={cn(chip, 'text-muted-foreground')}>
                    <Clock className="size-3.5" aria-hidden="true" />
                    {profile.hours}
                </span>
            ) : null}
            {socials.map(([network, url]) => {
                const social = socialIcons[network];

                if (!social) {
                    return null;
                }

                return (
                    <a
                        key={network}
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={social.label}
                        title={social.label}
                        className="inline-flex size-9 items-center justify-center rounded-full border text-muted-foreground transition-colors hover:border-primary/40 hover:text-primary"
                    >
                        <social.icon className="size-4" />
                    </a>
                );
            })}
        </div>
    );
}
