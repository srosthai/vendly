import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import SiteSettingsController from '@/actions/App/Http/Controllers/Admin/SiteSettingsController';
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

export type AdminPaymentMethod = {
    id: number;
    name: string;
    sort: number;
    logo: string | null;
};

export function PaymentMethodSheet({
    method = null,
}: {
    method?: AdminPaymentMethod | null;
}) {
    const [open, setOpen] = useState(false);
    const [removeLogo, setRemoveLogo] = useState(false);
    const key = method?.id ?? 'new';

    return (
        <FormSheet
            open={open}
            onOpenChange={setOpen}
            title={method ? `Edit ${method.name}` : 'New payment method'}
            description="Shown under “We accept” in the website footer."
            trigger={
                method ? (
                    <Button variant="ghost" size="sm">
                        Edit
                    </Button>
                ) : (
                    <Button variant="outline" size="sm">
                        <Plus />
                        Add method
                    </Button>
                )
            }
        >
            <Form
                {...(method
                    ? SiteSettingsController.updatePaymentMethod.form(method.id)
                    : SiteSettingsController.storePaymentMethod.form())}
                encType="multipart/form-data"
                options={{ preserveScroll: true }}
                resetOnSuccess
                onSuccess={() => setOpen(false)}
                className="flex min-h-0 flex-1 flex-col"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSheetBody>
                            <div className="grid gap-2">
                                <Label htmlFor={`method-name-${key}`}>
                                    Name
                                </Label>
                                <Input
                                    id={`method-name-${key}`}
                                    name="name"
                                    required
                                    placeholder="ABA"
                                    defaultValue={method?.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`method-logo-${key}`}>
                                    Logo
                                </Label>
                                {method?.logo && !removeLogo ? (
                                    <img
                                        src={method.logo}
                                        alt=""
                                        className="h-10 w-auto self-start rounded-md border bg-white p-1"
                                    />
                                ) : null}
                                <Input
                                    id={`method-logo-${key}`}
                                    name="logo"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Optional. Without a logo, the name is shown.
                                    Up to 512 KB.
                                </p>
                                <InputError message={errors.logo} />
                            </div>
                            {method?.logo ? (
                                <div className="flex items-center justify-between gap-4 rounded-2xl border p-4">
                                    <Label htmlFor={`method-remove-${key}`}>
                                        Remove the logo
                                    </Label>
                                    <Switch
                                        id={`method-remove-${key}`}
                                        checked={removeLogo}
                                        onCheckedChange={setRemoveLogo}
                                    />
                                    <input
                                        type="hidden"
                                        name="remove_logo"
                                        value={removeLogo ? '1' : '0'}
                                    />
                                </div>
                            ) : null}
                            <div className="grid gap-2">
                                <Label htmlFor={`method-sort-${key}`}>
                                    Order
                                </Label>
                                <Input
                                    id={`method-sort-${key}`}
                                    name="sort"
                                    type="number"
                                    min={0}
                                    defaultValue={method?.sort ?? 0}
                                />
                                <InputError message={errors.sort} />
                            </div>
                        </FormSheetBody>
                        <FormSheetFooter>
                            <SheetClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </SheetClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {method ? 'Save method' : 'Add method'}
                            </Button>
                        </FormSheetFooter>
                    </>
                )}
            </Form>
        </FormSheet>
    );
}
