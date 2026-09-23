import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, Send } from 'lucide-react';
import TelegramLinkController from '@/actions/App/Http/Controllers/TelegramLinkController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import vendor from '@/routes/vendor';

export default function Telegram({
    connected,
    link,
}: {
    connected: boolean;
    link: string | null;
}) {
    return (
        <>
            <Head title="Telegram" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Telegram"
                    description="Buy requests from your store arrive in your Telegram chat."
                />
                <Card className="max-w-2xl gap-5 p-5 sm:p-6">
                    <div className="flex items-start gap-4">
                        <span
                            className={
                                connected
                                    ? 'flex size-11 shrink-0 items-center justify-center rounded-full bg-success/12 text-success'
                                    : 'flex size-11 shrink-0 items-center justify-center rounded-full bg-highlight/20 text-highlight-foreground dark:text-highlight'
                            }
                        >
                            {connected ? (
                                <CheckCircle2 aria-hidden="true" />
                            ) : (
                                <Send aria-hidden="true" />
                            )}
                        </span>
                        <div>
                            <CardTitle>
                                {connected
                                    ? 'Your chat is connected'
                                    : 'Connect your chat'}
                            </CardTitle>
                            <CardDescription className="mt-1">
                                {connected
                                    ? 'Every buy request and cart reaches your chat and the Vendly admin. Connect again to move requests to a different chat.'
                                    : 'Until you connect, buy requests only reach the Vendly admin.'}
                            </CardDescription>
                        </div>
                    </div>
                    {link ? (
                        <div className="flex flex-col gap-3 rounded-xl bg-secondary p-4 text-sm">
                            <p className="text-secondary-foreground">
                                Open the bot and tap Start. The link works for
                                15 minutes.
                            </p>
                            <Button asChild className="self-start">
                                <a href={link} target="_blank" rel="noreferrer">
                                    <Send />
                                    Open the Vendly bot
                                </a>
                            </Button>
                        </div>
                    ) : (
                        <Form
                            {...TelegramLinkController.store.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <div className="grid gap-2">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        variant={
                                            connected ? 'outline' : 'default'
                                        }
                                        className="justify-self-start"
                                    >
                                        {processing && <Spinner />}
                                        {connected
                                            ? 'Connect a different chat'
                                            : 'Connect Telegram'}
                                    </Button>
                                    <InputError message={errors.telegram} />
                                </div>
                            )}
                        </Form>
                    )}
                </Card>
            </div>
        </>
    );
}

Telegram.layout = {
    breadcrumbs: [{ title: 'Telegram', href: vendor.telegram() }],
};
