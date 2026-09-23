import { Form, Head } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import SiteSettingsController from '@/actions/App/Http/Controllers/Admin/SiteSettingsController';
import { PaymentMethodSheet } from '@/components/admin/payment-method-sheet';
import type { AdminPaymentMethod } from '@/components/admin/payment-method-sheet';
import { EmptyState } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import admin from '@/routes/admin';

type Settings = {
    company_name: string;
    address: string;
    phone: string;
    email: string;
    footer_text: string;
    social_links: Record<string, string>;
};

const networks: { key: string; label: string; placeholder: string }[] = [
    {
        key: 'facebook',
        label: 'Facebook',
        placeholder: 'https://facebook.com/yourpage',
    },
    {
        key: 'instagram',
        label: 'Instagram',
        placeholder: 'https://instagram.com/yourname',
    },
    {
        key: 'tiktok',
        label: 'TikTok',
        placeholder: 'https://tiktok.com/@yourname',
    },
    {
        key: 'youtube',
        label: 'YouTube',
        placeholder: 'https://youtube.com/@yourchannel',
    },
    {
        key: 'telegram',
        label: 'Telegram',
        placeholder: 'https://t.me/yourchannel',
    },
    {
        key: 'linkedin',
        label: 'LinkedIn',
        placeholder: 'https://linkedin.com/company/yourcompany',
    },
];

export default function Site({
    settings,
    paymentMethods,
}: {
    settings: Settings;
    paymentMethods: AdminPaymentMethod[];
}) {
    return (
        <>
            <Head title="Site settings" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Site settings"
                    description="What the public website footer shows. Empty fields are hidden."
                />
                <Form
                    {...SiteSettingsController.update.form()}
                    options={{ preserveScroll: true }}
                    className="grid gap-4 lg:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="gap-5 p-5 sm:p-6">
                                <div>
                                    <CardTitle>Company and contact</CardTitle>
                                    <CardDescription className="mt-1">
                                        Shown in the footer on every page.
                                    </CardDescription>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="company_name">
                                        Company name
                                    </Label>
                                    <Input
                                        id="company_name"
                                        name="company_name"
                                        placeholder="Vendly"
                                        defaultValue={settings.company_name}
                                    />
                                    <InputError message={errors.company_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="address">Address</Label>
                                    <Input
                                        id="address"
                                        name="address"
                                        placeholder="Street, Sangkat, Phnom Penh"
                                        defaultValue={settings.address}
                                    />
                                    <InputError message={errors.address} />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            placeholder="+855 12 345 678"
                                            defaultValue={settings.phone}
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            placeholder="hello@vendly.example"
                                            defaultValue={settings.email}
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="footer_text">
                                        Footer text
                                    </Label>
                                    <Textarea
                                        id="footer_text"
                                        name="footer_text"
                                        rows={3}
                                        maxLength={300}
                                        placeholder="One link for your shop, on the web and inside Telegram."
                                        defaultValue={settings.footer_text}
                                    />
                                    <InputError message={errors.footer_text} />
                                </div>
                            </Card>
                            <Card className="gap-5 p-5 sm:p-6">
                                <div>
                                    <CardTitle>Social media</CardTitle>
                                    <CardDescription className="mt-1">
                                        Full links that start with https://.
                                    </CardDescription>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {networks.map((network) => (
                                        <div
                                            key={network.key}
                                            className="grid gap-2"
                                        >
                                            <Label
                                                htmlFor={`social-${network.key}`}
                                            >
                                                {network.label}
                                            </Label>
                                            <Input
                                                id={`social-${network.key}`}
                                                name={`social_links[${network.key}]`}
                                                type="url"
                                                placeholder={
                                                    network.placeholder
                                                }
                                                defaultValue={
                                                    settings.social_links[
                                                        network.key
                                                    ]
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `social_links.${network.key}`
                                                    ]
                                                }
                                            />
                                        </div>
                                    ))}
                                </div>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="mt-auto self-start"
                                >
                                    {processing && <Spinner />}
                                    Save site settings
                                </Button>
                            </Card>
                        </>
                    )}
                </Form>
                <Card className="gap-5 p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <CardTitle>We accept</CardTitle>
                            <CardDescription className="mt-1">
                                Payment methods listed in the footer, such as
                                ABA or Wing.
                            </CardDescription>
                        </div>
                        <PaymentMethodSheet />
                    </div>
                    {paymentMethods.length === 0 ? (
                        <EmptyState
                            bare
                            icon={CreditCard}
                            title="No payment methods yet"
                            description="Add the methods customers can use, with their logos."
                        />
                    ) : (
                        <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {paymentMethods.map((method) => (
                                <li
                                    key={method.id}
                                    className="flex items-center justify-between gap-3 rounded-2xl border p-3"
                                >
                                    <span className="flex min-w-0 items-center gap-3">
                                        <span className="flex h-10 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-white">
                                            {method.logo ? (
                                                <img
                                                    src={method.logo}
                                                    alt=""
                                                    className="max-h-8 max-w-14 object-contain"
                                                />
                                            ) : (
                                                <span className="text-sm font-semibold text-neutral-700">
                                                    {method.name}
                                                </span>
                                            )}
                                        </span>
                                        <span className="truncate font-medium">
                                            {method.name}
                                        </span>
                                    </span>
                                    <span className="flex shrink-0 gap-1">
                                        <PaymentMethodSheet method={method} />
                                        <ConfirmDeleteDialog
                                            name={method.name}
                                            description="It is removed from the website footer."
                                            action={SiteSettingsController.destroyPaymentMethod.form(
                                                method.id,
                                            )}
                                            triggerLabel="Remove"
                                        />
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </>
    );
}

Site.layout = {
    breadcrumbs: [{ title: 'Site settings', href: admin.site() }],
};
