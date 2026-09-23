import { Head } from '@inertiajs/react';
import { Tags } from 'lucide-react';
import type { AdminPlan } from '@/components/admin/plan-form-sheet';
import { PlanAvailabilitySwitch } from '@/components/admin/plan-availability-switch';
import { PlanFormSheet } from '@/components/admin/plan-form-sheet';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
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

export default function Plans({ plans }: { plans: AdminPlan[] }) {
    return (
        <>
            <Head title="Plans" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Plans"
                    description="Plans limit how many products a store can publish. The default plan is free, and every new store starts on it."
                    actions={<PlanFormSheet />}
                />
                {plans.length === 0 ? (
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
                                    {plans.map((plan) => (
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
                            {plans.map((plan) => (
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
                                            {plan.product_limit} products
                                        </p>
                                    </div>
                                    <div className="flex flex-col items-end gap-2">
                                        <PlanAvailabilitySwitch plan={plan} />
                                        <PlanFormSheet plan={plan} />
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

Plans.layout = {
    breadcrumbs: [{ title: 'Plans', href: admin.plans() }],
};
