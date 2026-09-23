import { Form, Head } from '@inertiajs/react';
import WorkspaceController from '@/actions/App/Http/Controllers/Vendor/WorkspaceController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { StoreMark } from '@/components/storefront/store-header';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ShareLinks } from '@/components/vendor/share-links';
import vendor from '@/routes/vendor';

type StoreProps = {
    name: string;
    slug: string;
    logo: string | null;
    description: string;
    web_url: string;
    telegram_url: string | null;
};

export default function StorePage({ store }: { store: StoreProps }) {
    return (
        <>
            <Head title="Store" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Store"
                    description="What customers see at the top of your store, and the links to share."
                    actions={
                        <Button asChild variant="outline">
                            <a href={store.web_url}>View store</a>
                        </Button>
                    }
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="gap-5 p-5 sm:p-6">
                        <div>
                            <CardTitle>Details</CardTitle>
                            <CardDescription className="mt-1">
                                Shown in the store header.
                            </CardDescription>
                        </div>
                        <Form
                            {...WorkspaceController.updateStore.form()}
                            encType="multipart/form-data"
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['logo']}
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="flex items-center gap-4">
                                        <StoreMark
                                            store={store}
                                            className="size-16 text-2xl"
                                        />
                                        <div className="grid flex-1 gap-2">
                                            <Label htmlFor="logo">Logo</Label>
                                            <Input
                                                id="logo"
                                                name="logo"
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp"
                                            />
                                            <p className="text-xs text-muted-foreground">
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
                                            defaultValue={store.name}
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
                                            rows={3}
                                            defaultValue={store.description}
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="justify-self-start"
                                    >
                                        {processing && <Spinner />}
                                        Save store
                                    </Button>
                                </>
                            )}
                        </Form>
                    </Card>
                    <Card className="gap-5 p-5 sm:p-6">
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
        </>
    );
}

StorePage.layout = {
    breadcrumbs: [{ title: 'Store', href: vendor.store() }],
};
