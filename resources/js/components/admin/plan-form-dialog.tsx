import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import PlanController from '@/actions/App/Http/Controllers/Billing/PlanController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export type AdminPlan = {
    id: number;
    name: string;
    price_cents: number;
    product_limit: number;
    is_active: boolean;
    is_default: boolean;
};

/**
 * Create a plan, or edit one when a plan is passed.
 */
export function PlanFormDialog({ plan = null }: { plan?: AdminPlan | null }) {
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(plan?.is_active ?? true);
    const [isDefault, setIsDefault] = useState(plan?.is_default ?? false);
    const key = plan?.id ?? 'new';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {plan ? (
                    <Button variant="ghost" size="sm">
                        Edit
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New plan
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {plan ? `Edit ${plan.name}` : 'New plan'}
                    </DialogTitle>
                    <DialogDescription>
                        The default plan is the free plan every new store starts
                        on.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...(plan
                        ? PlanController.update.form(plan.id)
                        : PlanController.store.form())}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={plan === null}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`name-${key}`}>Name</Label>
                                <Input
                                    id={`name-${key}`}
                                    name="name"
                                    required
                                    defaultValue={plan?.name}
                                    placeholder="Starter"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`price-${key}`}>
                                    Monthly price (USD)
                                </Label>
                                <Input
                                    id={`price-${key}`}
                                    name="price"
                                    required
                                    inputMode="decimal"
                                    placeholder="5.00"
                                    defaultValue={
                                        plan
                                            ? (plan.price_cents / 100).toFixed(
                                                  2,
                                              )
                                            : ''
                                    }
                                />
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`limit-${key}`}>
                                    Published product limit
                                </Label>
                                <Input
                                    id={`limit-${key}`}
                                    name="product_limit"
                                    type="number"
                                    min={0}
                                    required
                                    defaultValue={plan?.product_limit ?? 100}
                                />
                                <InputError message={errors.product_limit} />
                            </div>
                            <input
                                type="hidden"
                                name="is_active"
                                value={active ? '1' : '0'}
                            />
                            <input
                                type="hidden"
                                name="is_default"
                                value={isDefault ? '1' : '0'}
                            />
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id={`active-${key}`}
                                    checked={active}
                                    onCheckedChange={(checked) =>
                                        setActive(checked === true)
                                    }
                                />
                                <Label htmlFor={`active-${key}`}>
                                    Vendors can choose this plan
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id={`default-${key}`}
                                    checked={isDefault}
                                    onCheckedChange={(checked) =>
                                        setIsDefault(checked === true)
                                    }
                                />
                                <Label htmlFor={`default-${key}`}>
                                    New stores start on this plan
                                </Label>
                            </div>
                            <InputError message={errors.is_default} />
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {plan ? 'Save plan' : 'Create plan'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
