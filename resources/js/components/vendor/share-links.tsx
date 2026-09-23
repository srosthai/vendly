import { CopyField } from '@/components/copy-field';
/**
 * The two links a vendor shares: the web store and the Telegram mini app.
 */
export function ShareLinks({
    webUrl,
    telegramUrl,
}: {
    webUrl: string;
    telegramUrl: string | null;
}) {
    return (
        <div className="grid gap-4">
            <CopyField label="Web link" value={webUrl} />
            {telegramUrl ? (
                <CopyField label="Telegram link" value={telegramUrl} />
            ) : (
                <p className="text-sm text-muted-foreground">
                    The Telegram link appears once the Vendly admin sets up the
                    bot.
                </p>
            )}
        </div>
    );
}
