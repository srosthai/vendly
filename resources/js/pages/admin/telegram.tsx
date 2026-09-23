import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Secrets = {
    bot_token: boolean;
    cutluy_key: boolean;
    cutluy_webhook: boolean;
    telegram_webhook: boolean;
};

export default function Telegram({
    settings,
    secrets,
    webhookUrls,
}: {
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
            <div className="flex max-w-lg flex-col gap-8 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Telegram
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Secrets stay in the server environment.
                    </p>
                </div>
                <ul className="space-y-1 text-sm">
                    <li>
                        Bot token:{' '}
                        {secrets.bot_token ? 'Configured' : 'Missing'}
                    </li>
                    <li>
                        CutLuy key:{' '}
                        {secrets.cutluy_key ? 'Configured' : 'Missing'}
                    </li>
                    <li>
                        CutLuy webhook secret:{' '}
                        {secrets.cutluy_webhook ? 'Configured' : 'Missing'}
                    </li>
                    <li>
                        Telegram webhook secret:{' '}
                        {secrets.telegram_webhook ? 'Configured' : 'Missing'}
                    </li>
                </ul>
                <div className="space-y-2 text-sm text-muted-foreground">
                    <p>
                        Webhooks are refused until their secret is set. Point
                        CutLuy at <code>{webhookUrls.cutluy}</code>.
                    </p>
                    <p>
                        Register the bot webhook once with Telegram&apos;s{' '}
                        <code>setWebhook</code>, passing{' '}
                        <code>url={webhookUrls.telegram}</code> and{' '}
                        <code>secret_token</code> equal to{' '}
                        <code>TELEGRAM_WEBHOOK_SECRET</code>.
                    </p>
                </div>
                <Form
                    action="/admin/telegram"
                    method="put"
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="admin_chat_id">
                                    Admin chat id
                                </Label>
                                <Input
                                    id="admin_chat_id"
                                    name="admin_chat_id"
                                    defaultValue={settings.admin_chat_id}
                                />
                                <InputError message={errors.admin_chat_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="bot_username">
                                    Bot username
                                </Label>
                                <Input
                                    id="bot_username"
                                    name="bot_username"
                                    defaultValue={settings.bot_username}
                                />
                                <InputError message={errors.bot_username} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="mini_app_short_name">
                                    Mini app short name
                                </Label>
                                <Input
                                    id="mini_app_short_name"
                                    name="mini_app_short_name"
                                    defaultValue={settings.mini_app_short_name}
                                />
                                <InputError
                                    message={errors.mini_app_short_name}
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Save Telegram settings
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
