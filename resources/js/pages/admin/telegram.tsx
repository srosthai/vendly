import { Form, Head } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    ExternalLink,
    RefreshCw,
    Send,
} from 'lucide-react';
import TelegramSettingsController from '@/actions/App/Http/Controllers/Admin/TelegramSettingsController';
import TelegramTestController from '@/actions/App/Http/Controllers/Admin/TelegramTestController';
import { SecretField } from '@/components/admin/secret-field';
import type { SecretState } from '@/components/admin/secret-field';
import { CopyField } from '@/components/copy-field';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Result = { type: 'success' | 'error'; message: string };

type Webhook = {
    url: string;
    matches: boolean;
    pending: number;
    last_error: string | null;
    reachable: boolean;
};

function ResultNote({ result }: { result: Result | null }) {
    if (!result) {
        return null;
    }

    return (
        <p
            role="status"
            className={cn(
                'flex items-start gap-2 rounded-xl p-3 text-sm',
                result.type === 'success'
                    ? 'bg-success/10 text-success'
                    : 'bg-destructive/10 text-destructive',
            )}
        >
            {result.type === 'success' ? (
                <CheckCircle2
                    className="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
            ) : (
                <AlertCircle
                    className="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
            )}
            {result.message}
        </p>
    );
}

/**
 * What Telegram says about the webhook right now. It loads after the page,
 * because it asks Telegram.
 */
function WebhookStatus({
    webhook,
    hasToken,
}: {
    webhook: Webhook | null | undefined;
    hasToken: boolean;
}) {
    if (!hasToken) {
        return (
            <p className="text-sm text-muted-foreground">
                Add the bot token to connect the webhook.
            </p>
        );
    }

    if (webhook === undefined) {
        return (
            <div className="grid gap-2" aria-busy="true">
                <Skeleton className="h-5 w-40" />
                <Skeleton className="h-4 w-full" />
            </div>
        );
    }

    if (webhook === null || !webhook.reachable) {
        return (
            <p className="text-sm text-muted-foreground">
                Telegram could not be asked right now. Reload the page to try
                again.
            </p>
        );
    }

    return (
        <div className="grid gap-3">
            <div className="flex flex-wrap items-center gap-2">
                {webhook.matches ? (
                    <Badge variant="success">Connected to this site</Badge>
                ) : webhook.url === '' ? (
                    <Badge variant="destructive">Not registered</Badge>
                ) : (
                    <Badge variant="warning">Points somewhere else</Badge>
                )}
                {webhook.pending > 0 ? (
                    <Badge variant="secondary">{webhook.pending} waiting</Badge>
                ) : null}
            </div>
            {webhook.url !== '' && !webhook.matches ? (
                <p className="text-sm break-all text-muted-foreground">
                    Telegram sends updates to {webhook.url}. Register again to
                    point it here.
                </p>
            ) : null}
            {webhook.last_error ? (
                <p className="rounded-xl bg-warning/10 p-3 text-sm text-warning">
                    Last error from Telegram: {webhook.last_error}
                </p>
            ) : null}
        </div>
    );
}

export default function Telegram({
    settings,
    token,
    webhookUrl,
    cutluyWebhookUrl,
    cutluyReady,
    webhook,
    testResult,
    setupResult,
}: {
    settings: {
        admin_chat_id: string;
        bot_username: string;
        mini_app_short_name: string;
    };
    token: SecretState;
    webhookUrl: string;
    cutluyWebhookUrl: string;
    cutluyReady: boolean;
    webhook?: Webhook | null;
    testResult: Result | null;
    setupResult: Result | null;
}) {
    const hasToken = token.source !== null;

    return (
        <>
            <Head title="Telegram" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Telegram"
                    description="The one Vendly bot, its mini app, and the chat that receives every request. Paste the bot token and Vendly sets up the rest."
                />
                <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                    <Card className="gap-5 p-5 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle>Bot</CardTitle>
                                <CardDescription className="mt-1">
                                    Get the token from @BotFather. Saving it
                                    checks it with Telegram, fills in the bot
                                    username, and connects the webhook.
                                </CardDescription>
                            </div>
                            {settings.bot_username ? (
                                <a
                                    href={`https://t.me/${settings.bot_username}`}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 rounded-full bg-secondary px-3 py-1 text-sm font-medium text-secondary-foreground hover:underline"
                                >
                                    @{settings.bot_username}
                                    <ExternalLink
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                </a>
                            ) : null}
                        </div>
                        <Form
                            {...TelegramSettingsController.update.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['bot_token']}
                            className="grid gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <SecretField
                                        name="bot_token"
                                        label="Bot token"
                                        placeholder="123456789:ABC..."
                                        state={token}
                                        error={errors.bot_token}
                                    />
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <div className="grid content-start gap-2">
                                            <Label htmlFor="admin_chat_id">
                                                Admin chat id
                                            </Label>
                                            <Input
                                                id="admin_chat_id"
                                                name="admin_chat_id"
                                                inputMode="numeric"
                                                placeholder="5283073511"
                                                defaultValue={
                                                    settings.admin_chat_id
                                                }
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                A copy of every request goes
                                                here.
                                            </p>
                                            <InputError
                                                message={errors.admin_chat_id}
                                            />
                                        </div>
                                        <div className="grid content-start gap-2">
                                            <Label htmlFor="mini_app_short_name">
                                                Mini app short name
                                            </Label>
                                            <Input
                                                id="mini_app_short_name"
                                                name="mini_app_short_name"
                                                placeholder="shop"
                                                defaultValue={
                                                    settings.mini_app_short_name
                                                }
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                The name you gave the app in
                                                @BotFather with /newapp.
                                            </p>
                                            <InputError
                                                message={
                                                    errors.mini_app_short_name
                                                }
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="justify-self-start"
                                    >
                                        {processing && <Spinner />}
                                        Save and connect
                                    </Button>
                                </>
                            )}
                        </Form>
                        <ResultNote result={setupResult} />
                        <div className="flex flex-col gap-3 border-t pt-5">
                            <div>
                                <p className="font-medium">
                                    Check the connection
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Sends one message to the admin chat now.
                                </p>
                            </div>
                            <Form
                                {...TelegramTestController.form()}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {processing ? <Spinner /> : <Send />}
                                        Send test message
                                    </Button>
                                )}
                            </Form>
                            <ResultNote result={testResult} />
                        </div>
                    </Card>

                    <div className="flex flex-col gap-4">
                        <Card className="gap-4 p-5 sm:p-6">
                            <div>
                                <CardTitle>Webhook</CardTitle>
                                <CardDescription className="mt-1">
                                    How Telegram tells Vendly that a vendor
                                    tapped Start. Vendly registers it when you
                                    save the token.
                                </CardDescription>
                            </div>
                            <WebhookStatus
                                webhook={webhook}
                                hasToken={hasToken}
                            />
                            <CopyField
                                label="Webhook address"
                                value={webhookUrl}
                            />
                            {hasToken ? (
                                <Form
                                    {...TelegramSettingsController.registerWebhook.form()}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <RefreshCw />
                                            )}
                                            Register again
                                        </Button>
                                    )}
                                </Form>
                            ) : null}
                        </Card>
                        <Card className="gap-3 p-5 sm:p-6">
                            <div className="flex items-center justify-between gap-3">
                                <CardTitle>Payments</CardTitle>
                                {cutluyReady ? (
                                    <Badge variant="success">Ready</Badge>
                                ) : (
                                    <Badge variant="secondary">
                                        Not set up
                                    </Badge>
                                )}
                            </div>
                            <CardDescription>
                                CutLuy is set up in{' '}
                                <a
                                    href={admin.site.url()}
                                    className="text-primary hover:underline"
                                >
                                    Site settings
                                </a>
                                . Its webhook address is{' '}
                                <code className="break-all text-foreground">
                                    {cutluyWebhookUrl}
                                </code>
                                .
                            </CardDescription>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

Telegram.layout = {
    breadcrumbs: [{ title: 'Telegram', href: admin.telegram() }],
};
