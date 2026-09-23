import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Package, Plus } from 'lucide-react';
import type { ReactNode } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { ListToolbar } from '@/components/admin/list-toolbar';
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
import type { ComboboxOption } from '@/components/vendor/option-combobox';
import { ProductSheet } from '@/components/vendor/product-sheet';
import type { EditableProduct } from '@/components/vendor/product-sheet';
import vendor from '@/routes/vendor';

type ProductRow = {
    id: number;
    name: string;
    price_cents: number;
    status: string;
    stock: number | null;
    image: string | null;
    category: string | null;
    brand: string | null;
};

type Filters = {
    search: string;
    status: string;
    category: string;
    brand: string;
    sort: string;
};

const defaults: Filters = {
    search: '',
    status: 'all',
    category: 'all',
    brand: 'all',
    sort: 'newest',
};

const sheetProps = ['editing', 'creating'];

/**
 * The current list address with the product sheet opened for `edit`, for a
 * new product, or closed, keeping the list's filters and page.
 */
function useSheetUrl(): (sheet: { edit?: number; create?: boolean }) => string {
    const { url } = usePage();

    return (sheet) => {
        const next = new URL(url, 'http://vendly.local');
        next.searchParams.delete('edit');
        next.searchParams.delete('create');

        if (sheet.edit !== undefined) {
            next.searchParams.set('edit', String(sheet.edit));
        }

        if (sheet.create) {
            next.searchParams.set('create', '1');
        }

        return next.pathname + next.search;
    };
}

/**
 * A link that opens a product's sheet, loading only that product.
 */
function SheetLink({
    edit,
    create,
    className,
    children,
}: {
    edit?: number;
    create?: boolean;
    className?: string;
    children: ReactNode;
}) {
    const sheetUrl = useSheetUrl();

    return (
        <Link
            href={sheetUrl({ edit, create })}
            only={sheetProps}
            preserveState
            preserveScroll
            className={className}
        >
            {children}
        </Link>
    );
}

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
    filters,
    categories,
    brands,
    creating,
    editing,
    errors = {},
}: {
    usage: { published: number; limit: number };
    products: Paginated<ProductRow>;
    filters: Filters;
    categories: ComboboxOption[];
    brands: ComboboxOption[];
    creating: boolean;
    editing: EditableProduct | null;
    errors?: { status?: string };
}) {
    const full = usage.published >= usage.limit;
    const sheetUrl = useSheetUrl();
    const filtered = (Object.keys(defaults) as (keyof Filters)[]).some(
        (key) => key !== 'sort' && filters[key] !== defaults[key],
    );

    function closeSheet() {
        router.get(
            sheetUrl({}),
            {},
            {
                only: sheetProps,
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    const newProduct = (
        <Button asChild>
            <SheetLink create>
                <Plus />
                New product
            </SheetLink>
        </Button>
    );

    return (
        <>
            <Head title="Products" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Products"
                    description="Drafts stay private. Publish a product to show it in your store."
                    actions={newProduct}
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
                <ListToolbar
                    url={vendor.products.url()}
                    values={filters}
                    defaults={defaults}
                    searchPlaceholder="Search products by name"
                    filters={[
                        {
                            key: 'status',
                            label: 'Status',
                            options: [
                                { value: 'all', label: 'All statuses' },
                                { value: 'published', label: 'Published' },
                                { value: 'draft', label: 'Draft' },
                                { value: 'sold_out', label: 'Sold out' },
                            ],
                        },
                        ...(categories.length > 0
                            ? [
                                  {
                                      key: 'category',
                                      label: 'Category',
                                      options: [
                                          {
                                              value: 'all',
                                              label: 'All categories',
                                          },
                                          ...categories.map((category) => ({
                                              value: String(category.id),
                                              label: category.name,
                                          })),
                                      ],
                                  },
                              ]
                            : []),
                        ...(brands.length > 0
                            ? [
                                  {
                                      key: 'brand',
                                      label: 'Brand',
                                      options: [
                                          { value: 'all', label: 'All brands' },
                                          ...brands.map((brand) => ({
                                              value: String(brand.id),
                                              label: brand.name,
                                          })),
                                      ],
                                  },
                              ]
                            : []),
                    ]}
                    sorts={[
                        { value: 'newest', label: 'Newest first' },
                        { value: 'name', label: 'Name A to Z' },
                        { value: 'price_low', label: 'Price: low to high' },
                        { value: 'price_high', label: 'Price: high to low' },
                        { value: 'stock', label: 'Lowest stock' },
                    ]}
                    total={products.total ?? products.data.length}
                    noun={['product', 'products']}
                />
                {products.data.length === 0 ? (
                    filtered ? (
                        <EmptyState
                            icon={Package}
                            title="No products match these filters"
                            description="Try another search or filter, or clear them to see every product."
                            action={
                                <Button asChild variant="outline">
                                    <Link href={vendor.products()}>
                                        Clear filters
                                    </Link>
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            icon={Package}
                            title="No products yet"
                            description="Add your first product. It starts as a draft, so you can check it before customers see it."
                            action={newProduct}
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
                                        <TableHead>Category</TableHead>
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
                                                <SheetLink
                                                    edit={product.id}
                                                    className="flex items-center gap-3 font-medium hover:underline"
                                                >
                                                    <Thumbnail
                                                        product={product}
                                                    />
                                                    {product.name}
                                                </SheetLink>
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {dollars(product.price_cents)}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground tabular-nums">
                                                {product.stock ?? 'Not tracked'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {[
                                                    product.category,
                                                    product.brand,
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ') || 'None'}
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
                                    <SheetLink
                                        edit={product.id}
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
                                    </SheetLink>
                                    <RowActions product={product} full={full} />
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={products} />
                    </>
                )}
            </div>
            <ProductSheet
                open={creating || editing !== null}
                onClose={closeSheet}
                product={editing}
                categories={categories}
                brands={brands}
            />
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
                <SheetLink edit={product.id}>Edit</SheetLink>
            </Button>
            <ConfirmDeleteDialog
                name={product.name}
                description="The product and its photos are deleted. Requests already sent keep their copy."
                action={ProductController.destroy.form(product.id)}
            />
        </div>
    );
}

Products.layout = {
    breadcrumbs: [{ title: 'Products', href: vendor.products() }],
};
