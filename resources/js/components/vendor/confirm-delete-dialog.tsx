import { ConfirmActionDialog } from '@/components/confirm-action-dialog';
import { Button } from '@/components/ui/button';
import type { RouteFormDefinition } from '@/wayfinder';

export function ConfirmDeleteDialog({
    name,
    description,
    action,
    triggerLabel = 'Delete',
}: {
    name: string;
    description: string;
    action: RouteFormDefinition<'post'>;
    triggerLabel?: string;
}) {
    return (
        <ConfirmActionDialog
            title={`Delete ${name}?`}
            description={description}
            confirmLabel={`Delete ${name}`}
            action={action}
            trigger={
                <Button variant="ghost" size="sm" className="text-destructive">
                    {triggerLabel}
                </Button>
            }
        />
    );
}
