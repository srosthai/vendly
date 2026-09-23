import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import ProductImageController from '@/actions/App/Http/Controllers/ProductImageController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import { products as vendorProducts } from '@/routes/vendor';

type Option = { id: number; name: string };

type EditableProduct = {
    id: number;
    name: string;
    description: string;
    price: string;
    stock: number | null;
    category_id: number | null;
    brand_id: number | null;
    status: string;
    url: string | null;
    images: { id: number; url: string }[];
};

const none = 'none';

/**
 * A shadcn select that can be left empty. The hidden input sends an empty
 * value for "None", which the server reads as no category or brand.
 */
function OptionalSelect({
    id,
    name,
    label,
    options,
    defaultValue,
    error,
}: {
    id: string;
    name: string;
    label: string;
    options: Option[];
    defaultValue: number | null;
    error?: string;
}) {
    const [value, setValue] = useState(
        defaultValue === null ? none : String(defaultValue),
    );

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <input
                type="hidden"
                name={name}
                value={value === none ? '' : value}
            />
            <Select value={value} onValueChange={setValue}>
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={none}>None</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.id} value={String(option.id)}>
                            {option.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}

function Photos({ product }: { product: EditableProduct }) {
    if (product.images.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No photos yet. Customers see the first photo on the product
                card.
            </p>
        );
    }

    return (
        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {product.images.map((image, index) => (
                <li key={image.id} className="flex flex-col gap-2">
                    <div className="relative aspect-square overflow-hidden rounded-md bg-muted">
                        <img
                            src={image.url}
                            alt={`${product.name}, photo ${index + 1}`}
                            className="size-full object-cover"
                        />
                        {index === 0 ? (
                            <Badge className="absolute top-2 left-2">
                                Cover
                            </Badge>
                        ) : null}
                    </div>
                    <div className="flex flex-wrap gap-1">
                        {index > 0 ? (
                            <Form
                                {...ProductImageController.cover.form({
                                    product: product.id,
                                    image: image.id,
                                })}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        Make cover
                                    </Button>
                                )}
                            </Form>
                        ) : null}
                        <ConfirmDeleteDialog
                            name={`photo ${index + 1}`}
                            description="The photo is removed from the product and deleted."
                            action={ProductImageController.destroy.form({
                                product: product.id,
                                image: image.id,
                            })}
                            triggerLabel="Remove"
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default function ProductForm({
    product,
    categories,
    brands,
}: {
    product: EditableProduct | null;
    categories: Option[];
    brands: Option[];
}) {
    const title = product ? `Edit ${product.name}` : 'New product';
    const action = product
        ? ProductController.update.form(product.id)
        : ProductController.store.form();

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {product ? (
                            <Badge variant="secondary">
                                {product.status === 'published'
                                    ? 'Published'
                                    : 'Draft'}
                            </Badge>
                        ) : null}
                    </div>
                    <div className="flex gap-2">
                        {product?.url ? (
                            <Button variant="outline" size="sm" asChild>
                                <a href={product.url}>View in store</a>
                            </Button>
                        ) : null}
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={vendorProducts()}>All products</Link>
                        </Button>
                    </div>
                </div>
                <Form
                    {...action}
                    encType="multipart/form-data"
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['images[]']}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={product?.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="price">Price (USD)</Label>
                                    <Input
                                        id="price"
                                        name="price"
                                        required
                                        inputMode="decimal"
                                        placeholder="2.50"
                                        defaultValue={product?.price}
                                    />
                                    <InputError message={errors.price} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="stock">Stock</Label>
                                    <Input
                                        id="stock"
                                        name="stock"
                                        type="number"
                                        min={0}
                                        placeholder="Leave empty to not track"
                                        defaultValue={product?.stock ?? ''}
                                    />
                                    <InputError message={errors.stock} />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <OptionalSelect
                                    id="category_id"
                                    name="category_id"
                                    label="Category"
                                    options={categories}
                                    defaultValue={product?.category_id ?? null}
                                    error={errors.category_id}
                                />
                                <OptionalSelect
                                    id="brand_id"
                                    name="brand_id"
                                    label="Brand"
                                    options={brands}
                                    defaultValue={product?.brand_id ?? null}
                                    error={errors.brand_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={5}
                                    defaultValue={product?.description}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="images">
                                    {product ? 'Add photos' : 'Photos'}
                                </Label>
                                <Input
                                    id="images"
                                    name="images[]"
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                />
                                <p className="text-sm text-muted-foreground">
                                    JPEG, PNG, or WebP, up to 2 MB each and 8 at
                                    a time.
                                </p>
                                <InputError
                                    message={
                                        errors.images ??
                                        Object.entries(errors).find(([key]) =>
                                            key.startsWith('images.'),
                                        )?.[1]
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="justify-self-start"
                            >
                                {processing && <Spinner />}
                                {product ? 'Save product' : 'Save as draft'}
                            </Button>
                        </>
                    )}
                </Form>
                {product ? (
                    <>
                        <section className="flex flex-col gap-3">
                            <h2 className="text-lg font-medium">Photos</h2>
                            <Photos product={product} />
                        </section>
                        <section className="flex flex-col items-start gap-2 border-t pt-6">
                            <h2 className="text-lg font-medium">
                                Delete product
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Customers can no longer see it, and its photos
                                are deleted.
                            </p>
                            <ConfirmDeleteDialog
                                name={product.name}
                                description="The product and its photos are deleted. Requests already sent keep their copy."
                                action={ProductController.destroy.form(
                                    product.id,
                                )}
                            />
                        </section>
                    </>
                ) : null}
            </div>
        </>
    );
}
