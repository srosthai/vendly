import { Separator } from '@/components/ui/separator';

/**
 * A label between two rules. No background behind the label, so it sits on
 * a card, a dialog, or the page alike.
 */
export function OrSeparator({ label = 'or' }: { label?: string }) {
    return (
        <div
            className="my-1 flex items-center gap-3 text-sm text-muted-foreground"
            role="separator"
            aria-label={label}
        >
            <Separator className="flex-1" />
            <span aria-hidden="true">{label}</span>
            <Separator className="flex-1" />
        </div>
    );
}
