import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function Categories({
    categories,
}: {
    categories: { id: number; name: string }[];
}) {
    return (
        <>
            <Head title="Categories" />
            <NamedList
                title="Categories"
                empty="No categories yet."
                action="/categories"
                items={categories}
            />
        </>
    );
}

export function NamedList({
    title,
    empty,
    action,
    items,
}: {
    title: string;
    empty: string;
    action: string;
    items: { id: number; name: string }[];
}) {
    return (
        <div className="flex max-w-lg flex-col gap-6 p-4 md:p-6">
            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
            {items.length === 0 ? (
                <p className="text-muted-foreground">{empty}</p>
            ) : (
                <ul className="space-y-2 text-sm">
                    {items.map((item) => (
                        <li key={item.id}>{item.name}</li>
                    ))}
                </ul>
            )}
            <Form action={action} method="post" className="grid gap-4">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" name="name" required />
                            <InputError message={errors.name} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Add
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
