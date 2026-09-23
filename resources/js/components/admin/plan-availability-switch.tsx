import { router } from '@inertiajs/react';
import PlanAvailabilityController from '@/actions/App/Http/Controllers/Billing/PlanAvailabilityController';
import type { AdminPlan } from '@/components/admin/plan-form-sheet';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * Show or hide a plan for vendors in one tap. It flips at once and rolls
 * back if the server refuses. The default plan is locked on.
 */
export function PlanAvailabilitySwitch({ plan }: { plan: AdminPlan }) {
    const toggle = (available: boolean) => {
        router
            .optimistic<{ plans: AdminPlan[] }>((props) => ({
                plans: props.plans.map((item) =>
                    item.id === plan.id
                        ? { ...item, is_active: available }
                        : item,
                ),
            }))
            .patch(
                PlanAvailabilityController.url(plan.id),
                { is_active: available },
                { preserveScroll: true, preserveState: true },
            );
    };

    const control = (
        <span className="inline-flex items-center gap-2">
            <Switch
                checked={plan.is_active}
                onCheckedChange={toggle}
                disabled={plan.is_default}
                aria-label={`${plan.name} available to vendors`}
            />
            <span className="text-sm text-muted-foreground">
                {plan.is_active ? 'Available' : 'Hidden'}
            </span>
        </span>
    );

    if (!plan.is_default) {
        return control;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span tabIndex={0} className="rounded-full">
                    {control}
                </span>
            </TooltipTrigger>
            <TooltipContent>
                The default plan is always available.
            </TooltipContent>
        </Tooltip>
    );
}
