import { Separator } from '@/components/ui/separator';

export function OrSeparator({ label = 'or' }: { label?: string }) {
    return (
        <div className="relative my-1" role="separator" aria-label={label}>
            <div className="absolute inset-0 flex items-center">
                <Separator className="w-full" />
            </div>
            <div className="relative flex justify-center text-sm">
                <span className="bg-card px-3 text-muted-foreground">
                    {label}
                </span>
            </div>
        </div>
    );
}
