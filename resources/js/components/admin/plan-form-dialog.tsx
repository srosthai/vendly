import { Form } from '@inertiajs/react';
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

export function PlanFormDialog({ plan }: { plan: AdminPlan }) {
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(plan.is_active);
    const [isDefault, setIsDefault] = useState(plan.is_default);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Edit
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit {plan.name}</DialogTitle>
                    <DialogDescription>
                        The default plan is the free plan every new store starts
                        on.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...PlanController.update.form(plan.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`name-${plan.id}`}>Name</Label>
                                <Input
                                    id={`name-${plan.id}`}
                                    name="name"
                                    required
                                    defaultValue={plan.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`price-${plan.id}`}>
                                    Monthly price (USD)
                                </Label>
                                <Input
                                    id={`price-${plan.id}`}
                                    name="price"
                                    required
                                    defaultValue={(
                                        plan.price_cents / 100
                                    ).toFixed(2)}
                                />
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`limit-${plan.id}`}>
                                    Published product limit
                                </Label>
                                <Input
                                    id={`limit-${plan.id}`}
                                    name="product_limit"
                                    type="number"
                                    min={0}
                                    required
                                    defaultValue={plan.product_limit}
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
                                    id={`active-${plan.id}`}
                                    checked={active}
                                    onCheckedChange={(checked) =>
                                        setActive(checked === true)
                                    }
                                />
                                <Label htmlFor={`active-${plan.id}`}>
                                    Vendors can choose this plan
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id={`default-${plan.id}`}
                                    checked={isDefault}
                                    onCheckedChange={(checked) =>
                                        setIsDefault(checked === true)
                                    }
                                />
                                <Label htmlFor={`default-${plan.id}`}>
                                    New stores start on this plan
                                </Label>
                            </div>
                            <InputError message={errors.is_default} />
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save plan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
