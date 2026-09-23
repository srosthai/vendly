import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * A read-only value with a copy button, such as a store link or a webhook
 * address to paste somewhere else.
 */
export function CopyField({ label, value }: { label: string; value: string }) {
    const [copied, setCopied] = useState(false);
    const id = `copy-${label.toLowerCase().replace(/\s+/g, '-')}`;

    async function copy() {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            toast.success(`${label} copied.`);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            toast.error(
                'Copying is blocked here. Select the link and copy it.',
            );
        }
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <div className="flex gap-2">
                <Input
                    id={id}
                    readOnly
                    value={value}
                    onFocus={(event) => event.currentTarget.select()}
                />
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={copy}
                    aria-label={`Copy ${label.toLowerCase()}`}
                >
                    {copied ? <Check /> : <Copy />}
                </Button>
            </div>
        </div>
    );
}
