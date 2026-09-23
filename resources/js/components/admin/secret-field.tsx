import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export type SecretState = {
    source: 'admin' | 'env' | null;
    ends_with: string | null;
};

/**
 * A write-only credential. The saved value never reaches the browser: the
 * field shows where it comes from and its last four characters, an empty
 * field keeps it, and "Clear" removes the one saved here.
 */
export function SecretField({
    name,
    label,
    state,
    placeholder,
    error,
}: {
    name: string;
    label: string;
    state: SecretState;
    placeholder: string;
    error?: string;
}) {
    const [clear, setClear] = useState(false);
    const id = `secret-${name}`;

    return (
        <div className="grid content-start gap-2">
            <Label htmlFor={id}>{label}</Label>
            <PasswordInput
                id={id}
                name={name}
                autoComplete="off"
                disabled={clear}
                aria-describedby={`${id}-state`}
                placeholder={
                    state.source === null
                        ? placeholder
                        : 'Leave empty to keep the current value'
                }
            />
            <p className="text-sm text-muted-foreground" id={`${id}-state`}>
                {state.source === 'admin'
                    ? `Saved here, ending in ••••${state.ends_with}.`
                    : state.source === 'env'
                      ? `From the server environment, ending in ••••${state.ends_with}. A value saved here takes over.`
                      : 'Not set yet.'}
            </p>
            {state.source === 'admin' ? (
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={clear}
                        onCheckedChange={(checked) =>
                            setClear(checked === true)
                        }
                    />
                    Clear the saved value
                </label>
            ) : null}
            <input
                type="hidden"
                name={`clear_${name}`}
                value={clear ? '1' : '0'}
            />
            <InputError message={error} />
        </div>
    );
}
