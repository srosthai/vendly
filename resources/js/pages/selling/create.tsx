import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function CreateStore() {
    return (
        <>
            <Head title="Start selling" />
            <main className="mx-auto flex min-h-svh w-full max-w-lg flex-col justify-center gap-8 px-6 py-16">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">
                        <Link
                            href="/"
                            className="underline-offset-4 hover:underline"
                        >
                            Vendly
                        </Link>
                    </p>
                    <h1 className="text-4xl font-semibold tracking-tight">
                        Name your store
                    </h1>
                    <p className="text-muted-foreground">
                        This becomes the public link customers open.
                    </p>
                </div>
                <Form
                    action="/stores"
                    method="post"
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
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Input
                                    id="description"
                                    name="description"
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
