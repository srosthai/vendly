import { Check, ChevronsUpDown, X } from 'lucide-react';
import { useId, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type ComboboxOption = { id: number; name: string };

/**
 * A searchable picker for a category or brand. Type to filter, move with
 * the arrow keys, Enter to choose, Escape to close. "None" clears it. The
 * chosen id travels in a hidden input, empty for none.
 */
export function OptionCombobox({
    name,
    label,
    options,
    defaultValue,
    error,
}: {
    name: string;
    label: string;
    options: ComboboxOption[];
    defaultValue: number | null;
    error?: string;
}) {
    const id = useId();
    const listId = `${id}-list`;
    const input = useRef<HTMLInputElement>(null);
    const [value, setValue] = useState<number | null>(defaultValue);
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(0);
    const selected = options.find((option) => option.id === value) ?? null;

    const matches = useMemo(() => {
        const needle = query.trim().toLowerCase();
        const found = needle
            ? options.filter((option) =>
                  option.name.toLowerCase().includes(needle),
              )
            : options;

        return [{ id: 0, name: 'None' }, ...found];
    }, [options, query]);

    function choose(option: ComboboxOption) {
        setValue(option.id === 0 ? null : option.id);
        setQuery('');
        setOpen(false);
    }

    function onKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
            setActive((index) => Math.min(index + 1, matches.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter' && open) {
            event.preventDefault();
            const option = matches[active];

            if (option) {
                choose(option);
            }
        } else if (event.key === 'Escape' && open) {
            event.preventDefault();
            setOpen(false);
            setQuery('');
        }
    }

    return (
        <div className="grid content-start gap-2">
            <Label htmlFor={id}>{label}</Label>
            <input type="hidden" name={name} value={value ?? ''} />
            <div className="relative">
                <input
                    ref={input}
                    id={id}
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={listId}
                    aria-autocomplete="list"
                    aria-activedescendant={
                        open ? `${id}-option-${active}` : undefined
                    }
                    autoComplete="off"
                    value={open ? query : (selected?.name ?? '')}
                    placeholder={open ? 'Type to search' : 'None'}
                    onFocus={() => {
                        setOpen(true);
                        setActive(0);
                    }}
                    onBlur={() => {
                        setOpen(false);
                        setQuery('');
                    }}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setOpen(true);
                        setActive(event.target.value.trim() ? 1 : 0);
                    }}
                    onKeyDown={onKeyDown}
                    className="flex h-10 w-full rounded-full border border-input bg-transparent py-2 pr-16 pl-4 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                />
                <span className="pointer-events-none absolute top-1/2 right-3 flex -translate-y-1/2 items-center gap-1 text-muted-foreground">
                    <ChevronsUpDown className="size-4" aria-hidden="true" />
                </span>
                {selected && !open ? (
                    <button
                        type="button"
                        onClick={() => setValue(null)}
                        aria-label={`Clear ${label.toLowerCase()}`}
                        className="absolute top-1/2 right-8 flex size-7 -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground hover:bg-accent hover:text-foreground"
                    >
                        <X className="size-3.5" aria-hidden="true" />
                    </button>
                ) : null}
            </div>
            {open ? (
                <ul
                    id={listId}
                    role="listbox"
                    aria-label={label}
                    className="max-h-56 overflow-y-auto rounded-2xl border bg-popover p-1 text-popover-foreground shadow-md"
                >
                    {matches.map((option, index) => {
                        const isSelected =
                            option.id === 0
                                ? value === null
                                : option.id === value;

                        return (
                            <li
                                key={option.id}
                                id={`${id}-option-${index}`}
                                role="option"
                                aria-selected={isSelected}
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    choose(option);
                                }}
                                onMouseEnter={() => setActive(index)}
                                className={cn(
                                    'flex min-h-10 cursor-pointer items-center justify-between gap-2 rounded-xl px-3 text-sm',
                                    index === active &&
                                        'bg-accent text-accent-foreground',
                                    option.id === 0 && 'text-muted-foreground',
                                )}
                            >
                                {option.name}
                                {isSelected ? (
                                    <Check
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                ) : null}
                            </li>
                        );
                    })}
                    {matches.length === 1 && query.trim() !== '' ? (
                        <li className="px-3 py-2 text-sm text-muted-foreground">
                            Nothing matches “{query.trim()}”.
                        </li>
                    ) : null}
                </ul>
            ) : null}
            <InputError message={error} />
        </div>
    );
}
