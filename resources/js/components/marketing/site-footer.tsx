import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone } from 'lucide-react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import { socialIcons } from '@/components/social-icons';
import {
    features,
    home,
    howItWorks,
    login,
    pricing,
    register,
    testimonials,
} from '@/routes';
import { index as storesIndex } from '@/routes/stores';

export type SiteFooterData = {
    company_name: string;
    address: string | null;
    phone: string | null;
    email: string | null;
    footer_text: string | null;
    socials: Record<string, string>;
    payment_methods: { name: string; logo: string | null }[];
};

function FooterColumn({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div>
            <p className="text-sm font-semibold">{title}</p>
            <ul className="mt-4 space-y-3 text-sm">{children}</ul>
        </div>
    );
}

const linkClass =
    'text-muted-foreground transition-colors hover:text-foreground';

/**
 * The website footer. Everything in it comes from the admin's Site
 * settings, and anything left empty is simply not drawn.
 */
export function SiteFooter() {
    const { site, showTestimonials } = usePage<{
        site?: SiteFooterData;
        showTestimonials?: boolean;
    }>().props;

    if (!site) {
        return null;
    }

    const socials = Object.entries(site.socials).filter(
        ([network]) => socialIcons[network],
    );
    const hasContact = site.address || site.phone || site.email;

    return (
        <footer className="border-t bg-card">
            <div className="mx-auto grid max-w-6xl gap-12 px-4 py-14 md:px-6 lg:grid-cols-[minmax(0,1.3fr)_repeat(3,minmax(0,1fr))]">
                <div className="max-w-sm">
                    <Link
                        href={home()}
                        className="inline-flex min-h-11 items-center gap-1 rounded-md"
                        aria-label="Vendly home"
                    >
                        <AppLogo />
                    </Link>
                    <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                        {site.footer_text ||
                            'One link for your shop, on the web and inside Telegram.'}
                    </p>
                    {socials.length > 0 ? (
                        <ul
                            className="mt-6 flex flex-wrap gap-2"
                            aria-label="Social media"
                        >
                            {socials.map(([network, url]) => {
                                const { label, icon: Icon } =
                                    socialIcons[network];

                                return (
                                    <li key={network}>
                                        <a
                                            href={url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label={label}
                                            className="flex size-10 items-center justify-center rounded-full border bg-background text-muted-foreground transition-colors hover:border-primary/40 hover:text-primary"
                                        >
                                            <Icon className="size-4" />
                                        </a>
                                    </li>
                                );
                            })}
                        </ul>
                    ) : null}
                </div>

                <FooterColumn title="Product">
                    <li>
                        <Link href={features()} className={linkClass}>
                            Features
                        </Link>
                    </li>
                    <li>
                        <Link href={howItWorks()} className={linkClass}>
                            How it works
                        </Link>
                    </li>
                    <li>
                        <Link href={pricing()} className={linkClass}>
                            Pricing
                        </Link>
                    </li>
                    <li>
                        <Link href={storesIndex()} className={linkClass}>
                            Stores
                        </Link>
                    </li>
                    {showTestimonials ? (
                        <li>
                            <Link href={testimonials()} className={linkClass}>
                                Testimonials
                            </Link>
                        </li>
                    ) : null}
                </FooterColumn>

                <FooterColumn title="Get started">
                    <li>
                        <Link
                            href={register({ query: { next: 'sell' } })}
                            className={linkClass}
                        >
                            Start selling
                        </Link>
                    </li>
                    <li>
                        <Link href={login()} className={linkClass}>
                            Log in
                        </Link>
                    </li>
                </FooterColumn>

                {hasContact ? (
                    <FooterColumn title="Contact">
                        {site.address ? (
                            <li>
                                <a
                                    href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(site.address)}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={`flex items-start gap-2 ${linkClass}`}
                                >
                                    <MapPin
                                        className="mt-0.5 size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {site.address}
                                </a>
                            </li>
                        ) : null}
                        {site.phone ? (
                            <li>
                                <a
                                    href={`tel:${site.phone.replace(/[^0-9+]/g, '')}`}
                                    className={`flex items-center gap-2 ${linkClass}`}
                                >
                                    <Phone
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {site.phone}
                                </a>
                            </li>
                        ) : null}
                        {site.email ? (
                            <li>
                                <a
                                    href={`mailto:${site.email}`}
                                    className={`flex items-center gap-2 break-all ${linkClass}`}
                                >
                                    <Mail
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {site.email}
                                </a>
                            </li>
                        ) : null}
                    </FooterColumn>
                ) : null}
            </div>

            <div className="border-t">
                <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-6 md:flex-row md:items-center md:justify-between md:px-6">
                    <p className="text-sm text-muted-foreground">
                        © {new Date().getFullYear()} {site.company_name}. All
                        rights reserved.
                    </p>
                    {site.payment_methods.length > 0 ? (
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="text-sm text-muted-foreground">
                                We accept
                            </span>
                            <ul
                                className="flex flex-wrap items-center gap-2"
                                aria-label="Accepted payment methods"
                            >
                                {site.payment_methods.map((method) => (
                                    <li
                                        key={method.name}
                                        className="flex h-8 min-w-12 items-center justify-center rounded-md border bg-white px-2"
                                        title={method.name}
                                    >
                                        {method.logo ? (
                                            <img
                                                src={method.logo}
                                                alt={method.name}
                                                className="max-h-5 max-w-16 object-contain"
                                            />
                                        ) : (
                                            <span className="text-sm font-semibold text-neutral-800">
                                                {method.name}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </div>
            </div>
        </footer>
    );
}
