import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * An on/off switch with the shadcn look, without a Radix dependency. It is a
 * real button with `role="switch"`, so Space and Enter toggle it.
 */
function Switch({
    checked,
    onCheckedChange,
    disabled,
    className,
    thumbClassName,
    thumb,
    ...props
}: Omit<React.ComponentProps<'button'>, 'onChange' | 'children'> & {
    checked: boolean;
    onCheckedChange?: (checked: boolean) => void;
    thumbClassName?: string;
    thumb?: React.ReactNode;
}) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            data-state={checked ? 'checked' : 'unchecked'}
            data-slot="switch"
            disabled={disabled}
            onClick={() => onCheckedChange?.(!checked)}
            className={cn(
                'peer inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-transparent p-0.5 transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
                className,
            )}
            {...props}
        >
            <span
                data-slot="switch-thumb"
                className={cn(
                    'pointer-events-none flex size-5 items-center justify-center rounded-full bg-white shadow-sm ring-0 transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)] data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0',
                    thumbClassName,
                )}
                data-state={checked ? 'checked' : 'unchecked'}
            >
                {thumb}
            </span>
        </button>
    );
}

export { Switch };
