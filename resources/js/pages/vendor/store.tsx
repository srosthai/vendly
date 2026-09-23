import { Form, Head } from '@inertiajs/react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type StoreProps = {
    name: string;
    description: string;
    web_url: string;
    telegram_url: string | null;
};

export default function StorePage({ store }: { store: StoreProps }) {
    async function copy(value: string) {
        await navigator.clipboard.writeText(value);
        toast.success('Copied');
    }

    return (
        <>
            <Head title="Store" />
            <div className="flex max-w-xl flex-col gap-8 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">Store</h1>
                <Form
                    action="/vendor/store"
                    method="put"
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
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
                                <Label htmlFor="description">Description</Label>
                                <Input
                                    id="description"
                                    name="description"
                                    defaultValue={store.description}
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Save changes
                            </Button>
                        </>
                    )}
                </Form>
                <div className="grid gap-3">
                    <Share
                        label="Web link"
                        value={store.web_url}
                        onCopy={copy}
                    />
                    {store.telegram_url ? (
                        <Share
                            label="Telegram link"
                            value={store.telegram_url}
                            onCopy={copy}
                        />
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Set the bot username in admin before the mini app
                            link is ready.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}

function Share({
    label,
    value,
    onCopy,
}: {
    label: string;
    value: string;
    onCopy: (value: string) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <div className="flex gap-2">
                <Input readOnly value={value} />
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => onCopy(value)}
                >
                    Copy
                </Button>
            </div>
        </div>
    );
}
