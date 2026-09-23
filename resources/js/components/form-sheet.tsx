import type { ReactNode } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

/**
 * A create or edit form in a drawer: from the right on a wide screen, and
 * from the bottom on a phone. The body scrolls and the footer with the save
 * button stays in view. `wide` gives long forms more room on a wide screen.
 */
export function FormSheet({
    open,
    onOpenChange,
    trigger,
    title,
    description,
    wide = false,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    trigger?: ReactNode;
    title: string;
    description?: string;
    wide?: boolean;
    children: ReactNode;
}) {
    const isMobile = useIsMobile();

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            {trigger ? <SheetTrigger asChild>{trigger}</SheetTrigger> : null}
            <SheetContent
                side={isMobile ? 'bottom' : 'right'}
                className={cn(
                    'gap-0 p-0 data-[state=closed]:duration-300 data-[state=open]:duration-500 data-[state=open]:ease-[cubic-bezier(0.32,0.72,0,1)]',
                    isMobile
                        ? 'max-h-[92dvh] rounded-t-3xl'
                        : wide
                          ? 'w-full sm:max-w-2xl'
                          : 'w-full sm:max-w-md',
                )}
            >
                {isMobile ? (
                    <div
                        className="mx-auto mt-3 h-1.5 w-12 shrink-0 rounded-full bg-muted"
                        aria-hidden="true"
                    />
                ) : null}
                <SheetHeader className="border-b px-6 py-5 pr-14">
                    <SheetTitle className="text-lg">{title}</SheetTitle>
                    {description ? (
                        <SheetDescription>{description}</SheetDescription>
                    ) : null}
                </SheetHeader>
                {children}
            </SheetContent>
        </Sheet>
    );
}

export function FormSheetBody({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'grid flex-1 content-start gap-5 overflow-y-auto px-6 py-6',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function FormSheetFooter({ children }: { children: ReactNode }) {
    return (
        <div className="flex flex-col-reverse gap-2 border-t bg-card px-6 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:flex-row sm:justify-end">
            {children}
        </div>
    );
}
