import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Plan = {
    id: number;
    name: string;
    price_cents: number;
    product_limit: number;
    is_active: boolean;
    is_default: boolean;
};

export default function Plans({ plans }: { plans: Plan[] }) {
    return (
        <>
            <Head title="Plans" />
            <div className="flex flex-col gap-8 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Plans
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        A paid price is at least $0.01. The free plan is the
                        default.
                    </p>
                </div>
                {plans.length === 0 ? (
                    <p className="text-muted-foreground">
                        Create the first plan.
                    </p>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>Products</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {plans.map((plan) => (
                                <TableRow key={plan.id}>
                                    <TableCell>{plan.name}</TableCell>
                                    <TableCell>
                                        ${(plan.price_cents / 100).toFixed(2)}
                                    </TableCell>
                                    <TableCell>{plan.product_limit}</TableCell>
                                    <TableCell>
                                        {plan.is_default
                                            ? 'Default'
                                            : plan.is_active
                                              ? 'Active'
                                              : 'Hidden'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
                <Form
                    action="/admin/plans"
                    method="post"
                    className="grid max-w-md gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <h2 className="text-lg font-medium">New plan</h2>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="price">
                                    Monthly price (USD)
                                </Label>
                                <Input
                                    id="price"
                                    name="price"
                                    required
                                    placeholder="5.00"
                                />
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="product_limit">
                                    Published product limit
                                </Label>
                                <Input
                                    id="product_limit"
                                    name="product_limit"
                                    type="number"
                                    min={0}
                                    required
                                    defaultValue={100}
                                />
                                <InputError message={errors.product_limit} />
                            </div>
                            <input type="hidden" name="is_active" value="1" />
                            <input type="hidden" name="is_default" value="0" />
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Create plan
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
