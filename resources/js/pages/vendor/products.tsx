import { Form, Head, Link } from '@inertiajs/react';
import { Package, Plus } from 'lucide-react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import vendor from '@/routes/vendor';
import {
    create as createProduct,
    edit as editProduct,
} from '@/routes/vendor/products';

type ProductRow = {
    id: number;
    name: string;
    price_cents: number;
    status: string;
    stock: number | null;
    image: string | null;
};

function dollars(cents: number): string {
    return `$${(cents / 100).toFixed(2)}`;
}

function Thumbnail({ product }: { product: ProductRow }) {
    return (
        <span className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-muted text-muted-foreground">
            {product.image ? (
                <img
                    src={product.image}
                    alt=""
                    className="size-full object-cover"
                />
            ) : (
                <Package className="size-4" aria-hidden="true" />
            )}
        </span>
    );
}

function StatusBadge({ product }: { product: ProductRow }) {
    if (product.stock === 0) {
        return <Badge variant="warning">Sold out</Badge>;
    }

    return product.status === 'published' ? (
        <Badge variant="success">Published</Badge>
    ) : (
        <Badge variant="secondary">Draft</Badge>
    );
}

export default function Products({
    usage,
    products,
    search,
    errors = {},
}: {
    usage: { published: number; limit: number };
    products: Paginated<ProductRow>;
    search: string;
    errors?: { status?: string };
}) {
    const full = usage.published >= usage.limit;

    return (
        <>
            <Head title="Products" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Products"
                    description="Drafts stay private. Publish a product to show it in your store."
                    actions={
                        <Button asChild>
                            <Link href={createProduct()}>
                                <Plus />
                                New product
                            </Link>
                        </Button>
                    }
                />
                <Card className="gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium">
                            {usage.published} of {usage.limit} published
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {full
                                ? 'Your plan is full. Upgrade to publish more.'
                                : `${usage.limit - usage.published} more can go live on this plan.`}
                        </p>
                    </div>
                    <Progress
                        value={usage.published}
                        max={usage.limit}
                        aria-label="Published products"
                        className="sm:max-w-xs"
                    />
                    {full ? (
                        <Button asChild variant="outline" size="sm">
                            <Link href={vendor.plan()}>Upgrade plan</Link>
                        </Button>
                    ) : null}
                </Card>
                {errors.status ? (
                    <p className="text-sm text-destructive" role="alert">
                        {errors.status}
                    </p>
                ) : null}
                {products.data.length === 0 ? (
                    search !== '' ? (
                        <EmptyState
                            icon={Package}
                            title={`No products match "${search}"`}
                            description="Check the spelling, or clear the search to see every product."
                            action={
                                <Button asChild variant="outline">
                                    <Link href={vendor.products()}>
                                        Clear search
                                    </Link>
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            icon={Package}
                            title="No products yet"
                            description="Add your first product. It starts as a draft, so you can check it before customers see it."
                            action={
                                <Button asChild>
                                    <Link href={createProduct()}>
                                        <Plus />
                                        New product
                                    </Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <>
                        <Card className="hidden gap-0 overflow-hidden p-0 md:flex">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-5">
                                            Product
                                        </TableHead>
                                        <TableHead>Price</TableHead>
                                        <TableHead>Stock</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="pr-5">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.data.map((product) => (
                                        <TableRow key={product.id}>
                                            <TableCell className="pl-5">
                                                <Link
                                                    href={editProduct(
                                                        product.id,
                                                    )}
                                                    className="flex items-center gap-3 font-medium hover:underline"
                                                >
                                                    <Thumbnail
                                                        product={product}
                                                    />
                                                    {product.name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {dollars(product.price_cents)}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground tabular-nums">
                                                {product.stock ?? 'Not tracked'}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    product={product}
                                                />
                                            </TableCell>
                                            <TableCell className="pr-5 text-right">
                                                <RowActions
                                                    product={product}
                                                    full={full}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Card>
                        <div className="flex flex-col gap-3 md:hidden">
                            {products.data.map((product) => (
                                <Card key={product.id} className="gap-3 p-4">
                                    <Link
                                        href={editProduct(product.id)}
                                        className="flex items-center gap-3"
                                    >
                                        <Thumbnail product={product} />
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate font-medium">
                                                {product.name}
                                            </span>
                                            <span className="text-sm text-muted-foreground tabular-nums">
                                                {dollars(product.price_cents)}
                                            </span>
                                        </span>
                                        <StatusBadge product={product} />
                                    </Link>
                                    <RowActions product={product} full={full} />
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={products} />
                    </>
                )}
            </div>
        </>
    );
}

function RowActions({ product, full }: { product: ProductRow; full: boolean }) {
    return (
        <div className="flex flex-wrap items-center justify-end gap-1">
            {product.status !== 'published' ? (
                <Form
                    {...ProductController.publish.form(product.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing || full}
                            title={full ? 'Your plan is full' : undefined}
                        >
                            Publish
                        </Button>
                    )}
                </Form>
            ) : null}
            <Button variant="ghost" size="sm" asChild>
                <Link href={editProduct(product.id)}>Edit</Link>
            </Button>
            <ConfirmDeleteDialog
                name={product.name}
                description="The product and its photos are deleted. Requests already sent keep their copy."
                action={ProductController.destroy.form(product.id)}
            />
        </div>
    );
}
