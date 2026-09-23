import { Moon, Sun } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

/**
 * One tap between light and dark. The thumb carries a sun or a moon so the
 * current theme is visible at a glance.
 */
export function ThemeSwitch({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const dark = resolvedAppearance === 'dark';

    return (
        <Switch
            checked={dark}
            onCheckedChange={(checked) =>
                updateAppearance(checked ? 'dark' : 'light')
            }
            aria-label="Dark mode"
            title={dark ? 'Switch to light mode' : 'Switch to dark mode'}
            className={cn(
                'h-8 w-14 data-[state=checked]:bg-secondary data-[state=unchecked]:bg-muted',
                className,
            )}
            thumbClassName="size-6 data-[state=checked]:translate-x-7 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=unchecked]:text-[var(--brand-orange)]"
            thumb={
                dark ? (
                    <Moon className="size-3.5" aria-hidden="true" />
                ) : (
                    <Sun className="size-3.5" aria-hidden="true" />
                )
            }
        />
    );
}
