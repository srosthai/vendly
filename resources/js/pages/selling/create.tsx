import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import InputError from '@/components/input-error';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { home } from '@/routes';

/**
 * The same shape the server accepts: lowercase letters, numbers, and single
 * dashes. A name with no Latin letters gives an empty suggestion, and the
 * server then picks a short random link.
 */
function suggestSlug(name: string, maxLength: number): string {
    return name
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, maxLength)
        .replace(/-+$/g, '');
}

export default function CreateStore({
    webBase,
    telegramBase,
    maxSlugLength,
}: {
    webBase: string;
    telegramBase: string | null;
    maxSlugLength: number;
}) {
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [slugEdited, setSlugEdited] = useState(false);
    const shownSlug = slugEdited ? slug : suggestSlug(name, maxSlugLength);
    const previewSlug = shownSlug || 'your-store';

    return (
        <>
            <Head title="Start selling" />
            <ThemeToggle className="absolute top-4 right-4" />
            <main className="mx-auto flex min-h-svh w-full max-w-lg flex-col justify-center gap-8 px-6 py-16">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">
                        <Link
                            href={home()}
                            className="underline-offset-4 hover:underline"
                        >
                            Vendly
                        </Link>
                    </p>
                    <h1 className="text-4xl font-semibold tracking-tight">
                        Name your store
                    </h1>
                    <p className="text-muted-foreground">
                        Customers open your store from its link, on the web or
                        inside Telegram.
                    </p>
                </div>
                <Form
                    {...StoreController.store.form()}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Store name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    placeholder="Smile Tea"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="slug">Store link</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    placeholder="smile-tea"
                                    maxLength={maxSlugLength}
                                    aria-describedby="slug-help"
                                    value={shownSlug}
                                    onChange={(event) => {
                                        setSlugEdited(true);
                                        setSlug(
                                            event.target.value
                                                .toLowerCase()
                                                .replace(/[^a-z0-9-]/g, ''),
                                        );
                                    }}
                                />
                                <div
                                    id="slug-help"
                                    className="space-y-1 text-sm text-muted-foreground"
                                >
                                    <p className="break-all">
                                        Web: {webBase}
                                        <span className="text-foreground">
                                            {previewSlug}
                                        </span>
                                    </p>
                                    {telegramBase ? (
                                        <p className="break-all">
                                            Telegram: {telegramBase}
                                            <span className="text-foreground">
                                                {previewSlug}
                                            </span>
                                        </p>
                                    ) : null}
                                    <p>
                                        Lowercase letters, numbers, and dashes.
                                        Leave it empty and Vendly picks one.
                                    </p>
                                </div>
                                <InputError message={errors.slug} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    placeholder="Tea and small cakes"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Create store
                            </Button>
                        </>
                    )}
                </Form>
            </main>
        </>
    );
}
