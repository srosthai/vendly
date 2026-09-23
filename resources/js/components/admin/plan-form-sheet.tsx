import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import PlanController from '@/actions/App/Http/Controllers/Billing/PlanController';
import {
    FormSheet,
    FormSheetBody,
    FormSheetFooter,
} from '@/components/form-sheet';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SheetClose } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';

export type AdminPlan = {
    id: number;
    name: string;
    price_cents: number;
    yearly_price_cents: number | null;
    product_limit: number;
    is_active: boolean;
    is_default: boolean;
};

/**
 * Create a plan, or edit one when a plan is passed.
 */
export function PlanFormSheet({ plan = null }: { plan?: AdminPlan | null }) {
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(plan?.is_active ?? true);
    const [isDefault, setIsDefault] = useState(plan?.is_default ?? false);
    const key = plan?.id ?? 'new';

    return (
        <FormSheet
            open={open}
            onOpenChange={setOpen}
            title={plan ? `Edit ${plan.name}` : 'New plan'}
            description="Plans set how many products a store can publish. The default plan is free, and every new store starts on it."
            trigger={
                plan ? (
                    <Button variant="ghost" size="sm">
                        Edit
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New plan
                    </Button>
                )
            }
        >
            <Form
                {...(plan
                    ? PlanController.update.form(plan.id)
                    : PlanController.store.form())}
                options={{ preserveScroll: true }}
                resetOnSuccess={plan === null}
                onSuccess={() => setOpen(false)}
                className="flex min-h-0 flex-1 flex-col"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSheetBody>
                            <div className="grid gap-2">
                                <Label htmlFor={`name-${key}`}>Name</Label>
                                <Input
                                    id={`name-${key}`}
                                    name="name"
                                    required
                                    placeholder="Starter"
                                    defaultValue={plan?.name}
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
                                <p className="text-sm text-muted-foreground">
                                    Use 0 for a free plan. A paid price is at
                                    least $0.01.
                                </p>
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`yearly-price-${key}`}>
                                    Yearly price (USD, optional)
                                </Label>
                                <Input
                                    id={`yearly-price-${key}`}
                                    name="yearly_price"
                                    inputMode="decimal"
                                    placeholder="50.00"
                                    defaultValue={
                                        plan?.yearly_price_cents
                                            ? (
                                                  plan.yearly_price_cents / 100
                                              ).toFixed(2)
                                            : ''
                                    }
                                />
                                <p className="text-sm text-muted-foreground">
                                    One payment for twelve months. Leave it
                                    empty to sell this plan monthly only.
                                </p>
                                <InputError message={errors.yearly_price} />
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
                            <div className="grid divide-y rounded-2xl border">
                                <div className="flex items-center justify-between gap-4 p-4">
                                    <Label
                                        htmlFor={`active-${key}`}
                                        className="grid gap-1"
                                    >
                                        Available to vendors
                                        <span className="text-sm font-normal text-muted-foreground">
                                            Hidden plans cannot be chosen.
                                        </span>
                                    </Label>
                                    <Switch
                                        id={`active-${key}`}
                                        checked={active}
                                        onCheckedChange={setActive}
                                    />
                                </div>
                                <div className="flex items-center justify-between gap-4 p-4">
                                    <Label
                                        htmlFor={`default-${key}`}
                                        className="grid gap-1"
                                    >
                                        Default plan
                                        <span className="text-sm font-normal text-muted-foreground">
                                            New stores start on it. It must be
                                            free.
                                        </span>
                                    </Label>
                                    <Switch
                                        id={`default-${key}`}
                                        checked={isDefault}
                                        onCheckedChange={setIsDefault}
                                    />
                                </div>
                            </div>
                            <InputError message={errors.is_active} />
                            <InputError message={errors.is_default} />
                        </FormSheetBody>
                        <FormSheetFooter>
                            <SheetClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </SheetClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {plan ? 'Save plan' : 'Create plan'}
                            </Button>
                        </FormSheetFooter>
                    </>
                )}
            </Form>
        </FormSheet>
    );
}
