import { Moon, Sun } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

/**
 * One round button that flips between light and dark. It shows the theme a
 * click leads to (a moon in light mode, a sun in dark mode), and the icons
 * turn into each other as it flips.
 */
export function ThemeToggle({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const dark = resolvedAppearance === 'dark';
    const label = dark ? 'Switch to light theme' : 'Switch to dark theme';

    return (
        <button
            type="button"
            onClick={() => updateAppearance(dark ? 'light' : 'dark')}
            aria-label={label}
            title={label}
            className={cn(
                'relative inline-flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-card text-foreground transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50',
                className,
            )}
        >
            <Moon
                aria-hidden="true"
                className={cn(
                    'absolute size-[18px] transition-[rotate,scale,opacity] duration-300 ease-out motion-reduce:transition-none',
                    dark
                        ? 'scale-50 -rotate-90 opacity-0'
                        : 'scale-100 rotate-0 opacity-100',
                )}
            />
            <Sun
                aria-hidden="true"
                className={cn(
                    'absolute size-[18px] text-[var(--brand-orange)] transition-[rotate,scale,opacity] duration-300 ease-out motion-reduce:transition-none',
                    dark
                        ? 'scale-100 rotate-0 opacity-100'
                        : 'scale-50 rotate-90 opacity-0',
                )}
            />
        </button>
    );
}
