import { Form, Head } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import WorkspaceController from '@/actions/App/Http/Controllers/Vendor/WorkspaceController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { socialIcons } from '@/components/social-icons';
import { StoreMark } from '@/components/storefront/store-header';
import { accents, StoreContact } from '@/components/storefront/store-profile';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ShareLinks } from '@/components/vendor/share-links';
import { cn } from '@/lib/utils';
import vendor from '@/routes/vendor';

type StoreProps = {
    name: string;
    slug: string;
    logo: string | null;
    description: string;
    web_url: string;
    telegram_url: string | null;
    accent: string | null;
    phone: string;
    address: string;
    hours: string;
    social_links: Record<string, string>;
};

const socialPlaceholders: Record<string, string> = {
    facebook: 'https://facebook.com/yourshop',
    instagram: 'https://instagram.com/yourshop',
    tiktok: 'https://tiktok.com/@yourshop',
    website: 'https://yourshop.com',
};

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <Card className="gap-5 p-5 sm:p-6">
            <div>
                <CardTitle>{title}</CardTitle>
                <CardDescription className="mt-1">
                    {description}
                </CardDescription>
            </div>
            {children}
        </Card>
    );
}

export default function StorePage({
    store,
    accents: accentKeys,
}: {
    store: StoreProps;
    accents: string[];
}) {
    const [name, setName] = useState(store.name);
    const [description, setDescription] = useState(store.description);
    const [accent, setAccent] = useState<string | null>(store.accent);
    const [phone, setPhone] = useState(store.phone);
    const [address, setAddress] = useState(store.address);
    const [hours, setHours] = useState(store.hours);
    const [socials, setSocials] = useState(store.social_links);
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);

    useEffect(
        () => () =>
            logoPreview ? URL.revokeObjectURL(logoPreview) : undefined,
        [logoPreview],
    );

    const previewStore = {
        name: name || 'Your store',
        slug: store.slug,
        logo: removeLogo ? null : (logoPreview ?? store.logo),
        accent,
    };

    const filledSocials = Object.fromEntries(
        Object.entries(socials).filter(([, url]) => url.startsWith('https://')),
    );

    return (
        <>
            <Head title="Store" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Store"
                    description="What customers see at the top of your store, how to reach you, and the links to share."
                    actions={
                        <Button asChild variant="outline">
                            <a
                                href={store.web_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                View store
                            </a>
                        </Button>
                    }
                />
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <Form
                        {...WorkspaceController.updateStore.form()}
                        encType="multipart/form-data"
                        options={{ preserveScroll: true }}
                        resetOnSuccess={['logo']}
                        onSuccess={() => {
                            setLogoPreview(null);
                            setRemoveLogo(false);
                        }}
                        className="flex min-w-0 flex-col gap-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Section
                                    title="Identity"
                                    description="Your name, logo, and color set the look of your store."
                                >
                                    <div className="flex flex-wrap items-center gap-4">
                                        <StoreMark
                                            store={previewStore}
                                            className="size-16 text-2xl"
                                        />
                                        <div className="grid min-w-0 flex-1 gap-2">
                                            <Label htmlFor="logo">Logo</Label>
                                            <Input
                                                id="logo"
                                                name="logo"
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp"
                                                onChange={(event) => {
                                                    const file =
                                                        event.target.files?.[0];
                                                    setRemoveLogo(false);
                                                    setLogoPreview(
                                                        file
                                                            ? URL.createObjectURL(
                                                                  file,
                                                              )
                                                            : null,
                                                    );
                                                }}
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                A square JPEG, PNG, or WebP up
                                                to 1 MB.
                                            </p>
                                            <InputError message={errors.logo} />
                                        </div>
                                    </div>
                                    {store.logo ? (
                                        <div className="flex items-center gap-3">
                                            <Checkbox
                                                id="remove_logo"
                                                name="remove_logo"
                                                value="1"
                                                checked={removeLogo}
                                                onCheckedChange={(checked) =>
                                                    setRemoveLogo(
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label htmlFor="remove_logo">
                                                Remove the logo
                                            </Label>
                                        </div>
                                    ) : null}
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={name}
                                            onChange={(event) =>
                                                setName(event.target.value)
                                            }
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            One-line description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={2}
                                            value={description}
                                            onChange={(event) =>
                                                setDescription(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <fieldset className="grid gap-2">
                                        <legend className="mb-2 text-sm font-medium">
                                            Color
                                        </legend>
                                        <input
                                            type="hidden"
                                            name="accent"
                                            value={accent ?? ''}
                                        />
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setAccent(null)}
                                                aria-pressed={accent === null}
                                                className={cn(
                                                    'inline-flex min-h-10 items-center rounded-full border px-4 text-sm transition-colors',
                                                    accent === null
                                                        ? 'border-primary text-primary'
                                                        : 'text-muted-foreground hover:text-foreground',
                                                )}
                                            >
                                                Default
                                            </button>
                                            {accentKeys.map((key) => {
                                                const option = accents[key];

                                                if (!option) {
                                                    return null;
                                                }

                                                return (
                                                    <button
                                                        key={key}
                                                        type="button"
                                                        onClick={() =>
                                                            setAccent(key)
                                                        }
                                                        aria-pressed={
                                                            accent === key
                                                        }
                                                        aria-label={
                                                            option.label
                                                        }
                                                        title={option.label}
                                                        className={cn(
                                                            'flex size-10 items-center justify-center rounded-full ring-offset-2 ring-offset-card transition-shadow outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                                            option.swatch,
                                                            accent === key &&
                                                                'ring-2 ring-foreground',
                                                        )}
                                                    >
                                                        {accent === key ? (
                                                            <Check
                                                                className={cn(
                                                                    'size-4',
                                                                    key ===
                                                                        'orange'
                                                                        ? 'text-[#081A3B]'
                                                                        : 'text-white',
                                                                )}
                                                                aria-hidden="true"
                                                            />
                                                        ) : null}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        <InputError message={errors.accent} />
                                    </fieldset>
                                </Section>

                                <Section
                                    title="Contact"
                                    description="Customers can call you, find you on a map, and see when you are open. Leave any of them empty to hide it."
                                >
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <div className="grid content-start gap-2">
                                            <Label htmlFor="phone">Phone</Label>
                                            <Input
                                                id="phone"
                                                name="phone"
                                                type="tel"
                                                autoComplete="tel"
                                                placeholder="+855 12 345 678"
                                                value={phone}
                                                onChange={(event) =>
                                                    setPhone(event.target.value)
                                                }
                                            />
                                            <InputError
                                                message={errors.phone}
                                            />
                                        </div>
                                        <div className="grid content-start gap-2">
                                            <Label htmlFor="hours">
                                                Opening hours
                                            </Label>
                                            <Input
                                                id="hours"
                                                name="hours"
                                                placeholder="Mon to Sat, 8:00 to 18:00"
                                                value={hours}
                                                onChange={(event) =>
                                                    setHours(event.target.value)
                                                }
                                            />
                                            <InputError
                                                message={errors.hours}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="address">
                                                Address or area
                                            </Label>
                                            <Input
                                                id="address"
                                                name="address"
                                                autoComplete="street-address"
                                                placeholder="Street 240, Phnom Penh"
                                                value={address}
                                                onChange={(event) =>
                                                    setAddress(
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                Opens in Google Maps for
                                                customers.
                                            </p>
                                            <InputError
                                                message={errors.address}
                                            />
                                        </div>
                                    </div>
                                </Section>

                                <Section
                                    title="Social"
                                    description="Full links that start with https://."
                                >
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        {Object.keys(socialPlaceholders).map(
                                            (network) => {
                                                const social =
                                                    socialIcons[network];

                                                return (
                                                    <div
                                                        key={network}
                                                        className="grid content-start gap-2"
                                                    >
                                                        <Label
                                                            htmlFor={`social-${network}`}
                                                        >
                                                            {social?.label ??
                                                                network}
                                                        </Label>
                                                        <Input
                                                            id={`social-${network}`}
                                                            name={`social_links[${network}]`}
                                                            inputMode="url"
                                                            placeholder={
                                                                socialPlaceholders[
                                                                    network
                                                                ]
                                                            }
                                                            value={
                                                                socials[
                                                                    network
                                                                ] ?? ''
                                                            }
                                                            onChange={(event) =>
                                                                setSocials(
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        [network]:
                                                                            event
                                                                                .target
                                                                                .value,
                                                                    }),
                                                                )
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `social_links.${network}`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                );
                                            },
                                        )}
                                    </div>
                                </Section>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="self-start"
                                    size="lg"
                                >
                                    {processing && <Spinner />}
                                    Save store
                                </Button>
                            </>
                        )}
                    </Form>

                    <div className="flex flex-col gap-6 xl:sticky xl:top-6">
                        <Card className="gap-4 p-5">
                            <div>
                                <CardTitle>Preview</CardTitle>
                                <CardDescription className="mt-1">
                                    The top of your store, as customers see it.
                                </CardDescription>
                            </div>
                            <div className="relative overflow-hidden rounded-3xl border bg-background p-4 pt-5">
                                {accent && accents[accent] ? (
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r',
                                            accents[accent].band,
                                        )}
                                    />
                                ) : null}
                                <div className="flex items-center gap-3">
                                    <StoreMark
                                        store={previewStore}
                                        className="size-12 text-lg"
                                    />
                                    <div className="min-w-0">
                                        <p className="truncate text-lg font-bold tracking-tight">
                                            {previewStore.name}
                                        </p>
                                        {description ? (
                                            <p className="line-clamp-2 text-sm text-muted-foreground">
                                                {description}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                                <StoreContact
                                    className="mt-3"
                                    profile={{
                                        accent,
                                        phone: phone || null,
                                        address: address || null,
                                        hours: hours || null,
                                        socials: filledSocials,
                                    }}
                                />
                            </div>
                        </Card>
                        <Card className="gap-5 p-5">
                            <div>
                                <CardTitle>Share links</CardTitle>
                                <CardDescription className="mt-1">
                                    Both open the same store.
                                </CardDescription>
                            </div>
                            <ShareLinks
                                webUrl={store.web_url}
                                telegramUrl={store.telegram_url}
                            />
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

StorePage.layout = {
    breadcrumbs: [{ title: 'Store', href: vendor.store() }],
};
