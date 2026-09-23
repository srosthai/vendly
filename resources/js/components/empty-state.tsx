import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * An empty list tells the person the one next step and offers it.
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card px-6 py-12 text-center">
            {Icon ? (
                <span className="flex size-12 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
                    <Icon className="size-5" aria-hidden="true" />
                </span>
            ) : null}
            <div className="max-w-sm space-y-1">
                <p className="font-medium">{title}</p>
                {description ? (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                ) : null}
            </div>
            {action}
        </div>
    );
}
