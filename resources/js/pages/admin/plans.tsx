import { Head } from '@inertiajs/react';
import { Tags } from 'lucide-react';
import type { AdminPlan } from '@/components/admin/plan-form-sheet';
import { PlanAvailabilitySwitch } from '@/components/admin/plan-availability-switch';
import { PlanFormSheet } from '@/components/admin/plan-form-sheet';
import { ListToolbar } from '@/components/admin/list-toolbar';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dollars } from '@/lib/format';
import admin from '@/routes/admin';

function price(plan: AdminPlan): string {
    if (plan.price_cents === 0) {
        return 'Free';
    }

    const monthly = `${dollars(plan.price_cents)} / month`;

    return plan.yearly_price_cents
        ? `${monthly}, ${dollars(plan.yearly_price_cents)} / year`
        : monthly;
}

type ListedPlan = AdminPlan & { stores_count: number };

type Filters = {
    search: string;
    availability: string;
    price: string;
    sort: string;
};

const defaults: Filters = {
    search: '',
    availability: 'all',
    price: 'all',
    sort: 'newest',
};

export default function Plans({
    plans,
    filters,
}: {
    plans: Paginated<ListedPlan>;
    filters: Filters;
}) {
    const filtered = (Object.keys(defaults) as (keyof Filters)[]).some(
        (key) => key !== 'sort' && filters[key] !== defaults[key],
    );

    return (
        <>
            <Head title="Plans" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Plans"
                    description="Plans limit how many products a store can publish. The default plan is free, and every new store starts on it."
                    actions={<PlanFormSheet />}
                />
                <ListToolbar
                    url={admin.plans.url()}
                    values={filters}
                    defaults={defaults}
                    searchPlaceholder="Search plans by name"
                    filters={[
                        {
                            key: 'availability',
                            label: 'Availability',
                            options: [
                                { value: 'all', label: 'Available or hidden' },
                                { value: 'available', label: 'Available' },
                                { value: 'hidden', label: 'Hidden' },
                            ],
                        },
                        {
                            key: 'price',
                            label: 'Price',
                            options: [
                                { value: 'all', label: 'Free or paid' },
                                { value: 'free', label: 'Free' },
                                { value: 'paid', label: 'Paid' },
                            ],
                        },
                    ]}
                    sorts={[
                        { value: 'newest', label: 'Newest first' },
                        { value: 'price', label: 'Lowest price' },
                        { value: 'limit', label: 'Smallest limit' },
                    ]}
                    total={plans.total ?? plans.data.length}
                    noun={['plan', 'plans']}
                />
                {plans.data.length === 0 && filtered ? (
                    <EmptyState
                        icon={Tags}
                        title="No plans match these filters"
                        description="Try another search or filter, or clear them to see every plan."
                    />
                ) : plans.data.length === 0 ? (
                    <EmptyState
                        icon={Tags}
                        title="No plans yet"
                        description="Create a free default plan first. New stores cannot open without one."
                        action={<PlanFormSheet />}
                    />
                ) : (
                    <>
                        <Card className="hidden gap-0 overflow-hidden p-0 md:flex">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-5">
                                            Plan
                                        </TableHead>
                                        <TableHead>Price</TableHead>
                                        <TableHead>
                                            Published products
                                        </TableHead>
                                        <TableHead>Stores</TableHead>
                                        <TableHead>
                                            Available to vendors
                                        </TableHead>
                                        <TableHead className="pr-5">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {plans.data.map((plan) => (
                                        <TableRow key={plan.id}>
                                            <TableCell className="pl-5 font-medium">
                                                <span className="flex items-center gap-2">
                                                    {plan.name}
                                                    {plan.is_default ? (
                                                        <Badge>Default</Badge>
                                                    ) : null}
                                                </span>
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {price(plan)}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                Up to {plan.product_limit}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {plan.stores_count}
                                            </TableCell>
                                            <TableCell>
                                                <PlanAvailabilitySwitch
                                                    plan={plan}
                                                />
                                            </TableCell>
                                            <TableCell className="pr-5 text-right">
                                                <PlanFormSheet plan={plan} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Card>
                        <div className="flex flex-col gap-3 md:hidden">
                            {plans.data.map((plan) => (
                                <Card
                                    key={plan.id}
                                    className="flex-row items-center justify-between gap-3 p-4"
                                >
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <p className="font-medium">
                                                {plan.name}
                                            </p>
                                            {plan.is_default ? (
                                                <Badge>Default</Badge>
                                            ) : null}
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {price(plan)}, up to{' '}
                                            {plan.product_limit} products,{' '}
                                            {plan.stores_count}{' '}
                                            {plan.stores_count === 1
                                                ? 'store'
                                                : 'stores'}
                                        </p>
                                    </div>
                                    <div className="flex flex-col items-end gap-2">
                                        <PlanAvailabilitySwitch plan={plan} />
                                        <PlanFormSheet plan={plan} />
                                    </div>
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={plans} />
                    </>
                )}
            </div>
        </>
    );
}

Plans.layout = {
    breadcrumbs: [{ title: 'Plans', href: admin.plans() }],
};
