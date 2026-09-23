import { Form } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import ProductImageController from '@/actions/App/Http/Controllers/ProductImageController';
import {
    FormSheet,
    FormSheetBody,
    FormSheetFooter,
} from '@/components/form-sheet';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import { OptionCombobox } from '@/components/vendor/option-combobox';
import type { ComboboxOption } from '@/components/vendor/option-combobox';

export type EditableProduct = {
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

const formId = 'product-form';

function Photos({ product }: { product: EditableProduct }) {
    if (product.images.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No photos yet. Add some above; customers see the first one on
                the product card.
            </p>
        );
    }

    return (
        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {product.images.map((image, index) => (
                <li key={image.id} className="flex flex-col gap-2">
                    <div className="relative aspect-square overflow-hidden rounded-2xl bg-muted">
                        <img
                            src={image.url}
                            alt={`${product.name}, photo ${index + 1}`}
                            className="size-full object-cover"
                        />
                        {index === 0 ? (
                            <Badge
                                variant="solid"
                                className="absolute top-2 left-2"
                            >
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

/**
 * Create or edit a product without leaving the list. Photos and deleting
 * have their own forms, so the product form covers only the fields and the
 * footer's save button submits it by id.
 */
export function ProductSheet({
    open,
    onClose,
    product,
    categories,
    brands,
}: {
    open: boolean;
    onClose: () => void;
    product: EditableProduct | null;
    categories: ComboboxOption[];
    brands: ComboboxOption[];
}) {
    const [saving, setSaving] = useState(false);
    const action = product
        ? ProductController.update.form(product.id)
        : ProductController.store.form();

    return (
        <FormSheet
            open={open}
            onOpenChange={(next) => (next ? undefined : onClose())}
            wide
            title={product ? `Edit ${product.name}` : 'New product'}
            description={
                product
                    ? 'Changes show in your store as soon as you save.'
                    : 'New products start as drafts. Publish when it is ready.'
            }
        >
            <FormSheetBody className="gap-8">
                <Form
                    key={product?.id ?? 'new'}
                    id={formId}
                    {...action}
                    encType="multipart/form-data"
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['images[]']}
                    onStart={() => setSaving(true)}
                    onFinish={() => setSaving(false)}
                    className="grid gap-5"
                >
                    {({ errors }) => (
                        <>
                            {product ? (
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant={
                                            product.status === 'published'
                                                ? 'success'
                                                : 'secondary'
                                        }
                                    >
                                        {product.status === 'published'
                                            ? 'Published'
                                            : 'Draft'}
                                    </Badge>
                                    {product.url ? (
                                        <a
                                            href={product.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                                        >
                                            View in store
                                            <ExternalLink
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                        </a>
                                    ) : null}
                                </div>
                            ) : null}
                            <div className="grid gap-2">
                                <Label htmlFor="product-name">Name</Label>
                                <Input
                                    id="product-name"
                                    name="name"
                                    required
                                    autoFocus={product === null}
                                    defaultValue={product?.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="product-price">
                                        Price (USD)
                                    </Label>
                                    <Input
                                        id="product-price"
                                        name="price"
                                        required
                                        inputMode="decimal"
                                        placeholder="2.50"
                                        defaultValue={product?.price}
                                    />
                                    <InputError message={errors.price} />
                                </div>
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="product-stock">Stock</Label>
                                    <Input
                                        id="product-stock"
                                        name="stock"
                                        type="number"
                                        min={0}
                                        placeholder="Leave empty to not track"
                                        defaultValue={product?.stock ?? ''}
                                    />
                                    <InputError message={errors.stock} />
                                </div>
                                <OptionCombobox
                                    name="category_id"
                                    label="Category"
                                    options={categories}
                                    defaultValue={product?.category_id ?? null}
                                    error={errors.category_id}
                                />
                                <OptionCombobox
                                    name="brand_id"
                                    label="Brand"
                                    options={brands}
                                    defaultValue={product?.brand_id ?? null}
                                    error={errors.brand_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="product-description">
                                    Description
                                </Label>
                                <Textarea
                                    id="product-description"
                                    name="description"
                                    rows={5}
                                    defaultValue={product?.description}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="product-images">
                                    {product ? 'Add photos' : 'Photos'}
                                </Label>
                                <Input
                                    id="product-images"
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
                        </>
                    )}
                </Form>

                {product ? (
                    <>
                        <section className="grid gap-3">
                            <h3 className="font-semibold">Photos</h3>
                            <Photos product={product} />
                        </section>
                        <section className="grid gap-2 rounded-2xl border border-destructive/30 p-4">
                            <h3 className="font-semibold">Delete product</h3>
                            <p className="text-sm text-muted-foreground">
                                Customers can no longer see it, and its photos
                                are deleted.
                            </p>
                            <div>
                                <ConfirmDeleteDialog
                                    name={product.name}
                                    description="The product and its photos are deleted. Requests already sent keep their copy."
                                    action={ProductController.destroy.form(
                                        product.id,
                                    )}
                                />
                            </div>
                        </section>
                    </>
                ) : null}
            </FormSheetBody>
            <FormSheetFooter>
                <Button type="button" variant="outline" onClick={onClose}>
                    {product ? 'Close' : 'Cancel'}
                </Button>
                <Button type="submit" form={formId} disabled={saving}>
                    {saving && <Spinner />}
                    {product ? 'Save product' : 'Save as draft'}
                </Button>
            </FormSheetFooter>
        </FormSheet>
    );
}
