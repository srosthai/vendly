import type { LucideIcon } from 'lucide-react';
import { Check, Monitor, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const options: { value: Appearance; label: string; icon: LucideIcon }[] = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];

/**
 * Light, dark, or follow the device (inside Telegram: follow Telegram). The
 * choice is the same one Settings > Appearance saves, so it follows the
 * person across the website, the mini app, and their dashboard.
 */
export function ThemeToggle({ className }: { className?: string }) {
    const { appearance, resolvedAppearance, updateAppearance } =
        useAppearance();
    const Icon = resolvedAppearance === 'dark' ? Moon : Sun;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    className={cn('size-11 shrink-0', className)}
                    aria-label={`Theme: ${options.find((option) => option.value === appearance)?.label ?? 'System'}. Change theme`}
                >
                    <Icon />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                {options.map((option) => (
                    <DropdownMenuItem
                        key={option.value}
                        onSelect={() => updateAppearance(option.value)}
                        className="min-h-10"
                    >
                        <option.icon aria-hidden="true" />
                        {option.label}
                        {appearance === option.value ? (
                            <Check
                                className="ml-auto text-primary"
                                aria-label="Selected"
                            />
                        ) : null}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
