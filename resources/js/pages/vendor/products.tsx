import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type ProductRow = {
    id: number;
    name: string;
    price_cents: number;
    status: string;
};

export default function Products({
    usage,
    products,
    errors = {},
}: {
    usage: { published: number; limit: number };
    products: ProductRow[];
    errors?: { status?: string };
}) {
    const full = usage.published >= usage.limit;

    return (
        <>
            <Head title="Products" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Products
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {usage.published} of {usage.limit} published
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/vendor/products/create">New product</Link>
                    </Button>
                </div>
                {errors.status ? (
                    <p className="text-sm text-destructive">{errors.status}</p>
                ) : null}
                {full ? (
                    <p className="text-sm text-muted-foreground">
                        This plan is full.
                    </p>
                ) : null}
                {products.length === 0 ? (
                    <p className="text-muted-foreground">
                        No products yet. Create the first one.
                    </p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Price</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.map((product) => (
                                        <TableRow key={product.id}>
                                            <TableCell>
                                                {product.name}
                                            </TableCell>
                                            <TableCell>
                                                $
                                                {(
                                                    product.price_cents / 100
                                                ).toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                {product.status === 'published'
                                                    ? 'Published'
                                                    : 'Draft'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Publish
                                                    product={product}
                                                    disabled={
                                                        full &&
                                                        product.status !==
                                                            'published'
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="flex flex-col gap-3 md:hidden">
                            {products.map((product) => (
                                <Card key={product.id} className="gap-3 p-4">
                                    <div className="font-medium">
                                        {product.name}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        $
                                        {(product.price_cents / 100).toFixed(2)}{' '}
                                        ·{' '}
                                        {product.status === 'published'
                                            ? 'Published'
                                            : 'Draft'}
                                    </p>
                                    <Publish
                                        product={product}
                                        disabled={
                                            full &&
                                            product.status !== 'published'
                                        }
                                    />
                                </Card>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

function Publish({
    product,
    disabled,
}: {
    product: ProductRow;
    disabled: boolean;
}) {
    if (product.status === 'published') {
        return null;
    }

    return (
        <Form action={`/products/${product.id}/publish`} method="post">
            {({ processing }) => (
                <Button
                    type="submit"
                    size="sm"
                    disabled={processing || disabled}
                >
                    Publish
                </Button>
            )}
        </Form>
    );
}
