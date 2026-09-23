import { Form } from '@inertiajs/react';
import StoreSuspensionController from '@/actions/App/Http/Controllers/StoreSuspensionController';
import { ConfirmActionDialog } from '@/components/confirm-action-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type SuspendableStore = { id: number; name: string; suspended: boolean };

export function StoreStatusBadge({ suspended }: { suspended: boolean }) {
    return suspended ? (
        <Badge variant="destructive">Suspended</Badge>
    ) : (
        <Badge variant="success">Live</Badge>
    );
}

/**
 * Suspend a store after a confirmation, or restore it in one click.
 */
export function StoreSuspendAction({
    store,
    size = 'sm',
}: {
    store: SuspendableStore;
    size?: 'sm' | 'default';
}) {
    if (store.suspended) {
        return (
            <Form
                {...StoreSuspensionController.destroy.form(store.id)}
                options={{ preserveScroll: true }}
            >
                {({ processing }) => (
                    <Button
                        type="submit"
                        variant="outline"
                        size={size}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Restore
                    </Button>
                )}
            </Form>
        );
    }

    return (
        <ConfirmActionDialog
            title={`Suspend ${store.name}?`}
            description="The store disappears for customers and the vendor cannot change it until you restore it."
            confirmLabel={`Suspend ${store.name}`}
            action={StoreSuspensionController.store.form(store.id)}
            trigger={
                <Button
                    variant={size === 'sm' ? 'ghost' : 'outline'}
                    size={size}
                    className="text-destructive"
                >
                    Suspend
                </Button>
            }
        />
    );
}
