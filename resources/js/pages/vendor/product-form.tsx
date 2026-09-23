import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function ProductForm() {
    return (
        <>
            <Head title="New product" />
            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    New product
                </h1>
                <Form
                    action="/products"
                    method="post"
                    encType="multipart/form-data"
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="price">Price (USD)</Label>
                                <Input
                                    id="price"
                                    name="price"
                                    required
                                    placeholder="2.50"
                                />
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Input id="description" name="description" />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="images">Photo</Label>
                                <Input
                                    id="images"
                                    name="images[]"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Save product
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
