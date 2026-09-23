import { Form, Head } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Send } from 'lucide-react';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import TelegramTestController from '@/actions/App/Http/Controllers/Admin/TelegramTestController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import admin from '@/routes/admin';

type Secrets = {
    bot_token: boolean;
    cutluy_key: boolean;
    cutluy_webhook: boolean;
    telegram_webhook: boolean;
};

const secretLabels: { key: keyof Secrets; label: string; env: string }[] = [
    { key: 'bot_token', label: 'Bot token', env: 'TELEGRAM_BOT_TOKEN' },
    {
        key: 'telegram_webhook',
        label: 'Telegram webhook secret',
        env: 'TELEGRAM_WEBHOOK_SECRET',
    },
    { key: 'cutluy_key', label: 'CutLuy API key', env: 'CUTLUY_API_KEY' },
    {
        key: 'cutluy_webhook',
        label: 'CutLuy webhook secret',
        env: 'CUTLUY_WEBHOOK_SECRET',
    },
];

export default function Telegram({
    settings,
    secrets,
    webhookUrls,
    testResult,
}: {
    testResult: { type: 'success' | 'error'; message: string } | null;
    settings: {
        admin_chat_id: string;
        bot_username: string;
        mini_app_short_name: string;
    };
    secrets: Secrets;
    webhookUrls: { telegram: string; cutluy: string };
}) {
    return (
        <>
            <Head title="Telegram" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Telegram and payments"
                    description="The one Vendly bot, its mini app, and the chat that receives every request."
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="gap-5 p-5 sm:p-6">
                        <div>
                            <CardTitle>Bot</CardTitle>
                            <CardDescription className="mt-1">
                                Store links and buy requests use these.
                            </CardDescription>
                        </div>
                        <Form
                            {...AdminDashboardController.updateTelegram.form()}
                            options={{ preserveScroll: true }}
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="bot_username">
                                            Bot username
                                        </Label>
                                        <Input
                                            id="bot_username"
                                            name="bot_username"
                                            placeholder="VendlyBot"
                                            defaultValue={settings.bot_username}
                                        />
                                        <InputError
                                            message={errors.bot_username}
                                        />
                                    </div>
                                    <div className="grid gap-2">
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
                                        <InputError
                                            message={errors.mini_app_short_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="admin_chat_id">
                                            Admin chat id
                                        </Label>
                                        <Input
                                            id="admin_chat_id"
                                            name="admin_chat_id"
                                            placeholder="-1001234567890"
                                            defaultValue={
                                                settings.admin_chat_id
                                            }
                                        />
                                        <InputError
                                            message={errors.admin_chat_id}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="justify-self-start"
                                    >
                                        {processing && <Spinner />}
                                        Save settings
                                    </Button>
                                </>
                            )}
                        </Form>
                        <div className="flex flex-col gap-3 border-t pt-5">
                            <div>
                                <p className="font-medium">
                                    Check the connection
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Sends one message to the admin chat now.
                                    Save your changes first.
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
                            {testResult ? (
                                <p
                                    role="status"
                                    className={
                                        testResult.type === 'success'
                                            ? 'flex items-start gap-2 rounded-xl bg-success/10 p-3 text-sm text-success'
                                            : 'flex items-start gap-2 rounded-xl bg-destructive/10 p-3 text-sm text-destructive'
                                    }
                                >
                                    {testResult.type === 'success' ? (
                                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                                    ) : (
                                        <AlertCircle className="mt-0.5 size-4 shrink-0" />
                                    )}
                                    {testResult.message}
                                </p>
                            ) : null}
                        </div>
                    </Card>
                    <Card className="gap-5 p-5 sm:p-6">
                        <div>
                            <CardTitle>Secrets and webhooks</CardTitle>
                            <CardDescription className="mt-1">
                                Secrets live in the server environment, never in
                                this page. Webhooks are refused until their
                                secret is set.
                            </CardDescription>
                        </div>
                        <ul className="divide-y rounded-xl border">
                            {secretLabels.map((secret) => (
                                <li
                                    key={secret.key}
                                    className="flex items-center justify-between gap-3 px-4 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium">
                                            {secret.label}
                                        </p>
                                        <p className="font-mono text-sm text-muted-foreground">
                                            {secret.env}
                                        </p>
                                    </div>
                                    {secrets[secret.key] ? (
                                        <Badge variant="success">Set</Badge>
                                    ) : (
                                        <Badge variant="destructive">
                                            Missing
                                        </Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                        <div className="space-y-2 text-sm text-muted-foreground">
                            <p>
                                Point CutLuy at{' '}
                                <code className="break-all text-foreground">
                                    {webhookUrls.cutluy}
                                </code>
                                .
                            </p>
                            <p>
                                Register the bot webhook once with
                                Telegram&apos;s <code>setWebhook</code>, using{' '}
                                <code className="break-all text-foreground">
                                    {webhookUrls.telegram}
                                </code>{' '}
                                and a <code>secret_token</code> equal to{' '}
                                <code>TELEGRAM_WEBHOOK_SECRET</code>.
                            </p>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}

Telegram.layout = {
    breadcrumbs: [{ title: 'Telegram', href: admin.telegram() }],
};
