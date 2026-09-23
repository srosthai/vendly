import { Form, Head, usePoll } from '@inertiajs/react';
import { Check, ChevronDown, Send } from 'lucide-react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import TelegramLinkController from '@/actions/App/Http/Controllers/TelegramLinkController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import vendor from '@/routes/vendor';

type Chat = { name: string | null; connected_at: string | null };

type TestResult = { type: 'success' | 'error'; message: string };

const help = [
    {
        question: 'I tapped Start, but this page still says waiting',
        answer: 'The link works for 15 minutes and only once. Tap Get a new link, open the bot again, and tap Start.',
    },
    {
        question: 'The test says I blocked the bot',
        answer: 'Open the Vendly bot in Telegram and tap Restart or Unblock, then send the test again.',
    },
    {
        question: 'I want requests in a different Telegram account',
        answer: 'Tap Connect a different chat, then open the bot and tap Start while signed in to the other account. The old chat stops getting requests.',
    },
    {
        question: 'Do customers see my Telegram account?',
        answer: 'No. Customers send requests through Vendly, and you choose how to reply to them.',
    },
];

/**
 * One step of the connection: its number or a check, and what to do.
 */
function Step({
    number,
    state,
    title,
    last = false,
    children,
}: {
    number: number;
    state: 'done' | 'current' | 'upcoming';
    title: string;
    last?: boolean;
    children?: ReactNode;
}) {
    return (
        <li className="relative flex gap-4 pb-8 last:pb-0">
            {last ? null : (
                <span
                    aria-hidden="true"
                    className={cn(
                        'absolute top-10 bottom-0 left-5 w-px',
                        state === 'done' ? 'bg-success/50' : 'bg-border',
                    )}
                />
            )}
            <span
                className={cn(
                    'relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                    state === 'done' && 'bg-success text-white',
                    state === 'current' &&
                        'bg-primary text-primary-foreground ring-4 ring-primary/15',
                    state === 'upcoming' &&
                        'border bg-card text-muted-foreground',
                )}
            >
                {state === 'done' ? (
                    <Check className="size-4" aria-hidden="true" />
                ) : (
                    number
                )}
                <span className="sr-only">
                    {state === 'done'
                        ? ', done'
                        : state === 'current'
                          ? ', to do now'
                          : ', next'}
                </span>
            </span>
            <div className="min-w-0 flex-1 pt-2">
                <p
                    className={cn(
                        'font-semibold',
                        state === 'upcoming' && 'text-muted-foreground',
                    )}
                >
                    {title}
                </p>
                {children ? <div className="mt-2">{children}</div> : null}
            </div>
        </li>
    );
}

function LinkButton({
    label,
    variant = 'default',
}: {
    label: string;
    variant?: 'default' | 'outline';
}) {
    return (
        <Form
            {...TelegramLinkController.store.form()}
            options={{ preserveScroll: true }}
        >
            {({ processing, errors }) => (
                <div className="grid gap-2">
                    <Button
                        type="submit"
                        variant={variant}
                        disabled={processing}
                        className="justify-self-start"
                    >
                        {processing && <Spinner />}
                        {label}
                    </Button>
                    <InputError message={errors.telegram} />
                </div>
            )}
        </Form>
    );
}

export default function Telegram({
    connected,
    chat,
    bot,
    link,
    testResult,
}: {
    connected: boolean;
    chat: Chat | null;
    bot: string | null;
    link: string | null;
    testResult: TestResult | null;
}) {
    const waiting = link !== null && !connected;
    const { start, stop } = usePoll(
        3000,
        { only: ['connected', 'chat'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (waiting) {
            start();
        } else {
            stop();
        }

        return stop;
    }, [waiting, start, stop]);

    const botName = bot ?? 'the Vendly bot';

    return (
        <>
            <Head title="Telegram" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Telegram"
                    description="Buy requests from your store arrive in your Telegram chat."
                >
                    {connected ? (
                        <Badge variant="success">Connected</Badge>
                    ) : (
                        <Badge variant="secondary">Not connected</Badge>
                    )}
                </PageHeader>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <Card className="gap-6 p-5 sm:p-6">
                        <div>
                            <CardTitle>
                                {connected
                                    ? 'Your chat is connected'
                                    : 'Connect your chat in three steps'}
                            </CardTitle>
                            <CardDescription className="mt-1">
                                {connected
                                    ? 'Every buy request and cart reaches this chat. The Vendly admin keeps a copy too.'
                                    : 'Until you connect, buy requests only reach the Vendly admin.'}
                            </CardDescription>
                        </div>

                        <ol>
                            <Step
                                number={1}
                                state={
                                    connected || link !== null
                                        ? 'done'
                                        : 'current'
                                }
                                title="Get your connection link"
                            >
                                {!connected && link === null ? (
                                    <LinkButton label="Get my link" />
                                ) : null}
                            </Step>
                            <Step
                                number={2}
                                state={
                                    connected
                                        ? 'done'
                                        : link !== null
                                          ? 'current'
                                          : 'upcoming'
                                }
                                title={`Open ${botName} and tap Start`}
                            >
                                {waiting && link ? (
                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button asChild>
                                            <a
                                                href={link}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <Send />
                                                Open {botName}
                                            </a>
                                        </Button>
                                        <p className="text-sm text-muted-foreground">
                                            The link works for 15 minutes.
                                        </p>
                                    </div>
                                ) : null}
                            </Step>
                            <Step
                                number={3}
                                last
                                state={connected ? 'done' : 'upcoming'}
                                title={
                                    connected
                                        ? 'Connected'
                                        : 'See Connected here'
                                }
                            >
                                {waiting ? (
                                    <p
                                        role="status"
                                        className="flex items-center gap-2 text-sm text-muted-foreground"
                                    >
                                        <span className="relative flex size-2.5">
                                            <span className="absolute inline-flex size-full animate-ping rounded-full bg-primary/60 motion-reduce:animate-none" />
                                            <span className="relative inline-flex size-2.5 rounded-full bg-primary" />
                                        </span>
                                        Waiting for Telegram. This page updates
                                        on its own.
                                    </p>
                                ) : null}
                                {connected && chat ? (
                                    <p className="text-sm text-muted-foreground">
                                        {chat.name
                                            ? `Chat: ${chat.name}`
                                            : 'Your Telegram chat'}
                                        {chat.connected_at
                                            ? `, since ${formatDateTime(chat.connected_at)}`
                                            : ''}
                                    </p>
                                ) : null}
                            </Step>
                        </ol>

                        {waiting ? (
                            <div className="border-t pt-5">
                                <LinkButton
                                    label="Get a new link"
                                    variant="outline"
                                />
                            </div>
                        ) : null}
                    </Card>

                    <div className="flex flex-col gap-6">
                        {connected ? (
                            <Card className="gap-4 p-5">
                                <div>
                                    <CardTitle>Check it works</CardTitle>
                                    <CardDescription className="mt-1">
                                        Send a test to your chat now.
                                    </CardDescription>
                                </div>
                                <Form
                                    {...TelegramLinkController.test.form()}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Send />
                                            )}
                                            Send test message
                                        </Button>
                                    )}
                                </Form>
                                {testResult ? (
                                    <p
                                        role="status"
                                        className={cn(
                                            'rounded-2xl px-4 py-3 text-sm',
                                            testResult.type === 'success'
                                                ? 'bg-success/10 text-success'
                                                : 'bg-destructive/10 text-destructive',
                                        )}
                                    >
                                        {testResult.message}
                                    </p>
                                ) : null}
                                <div className="border-t pt-4">
                                    <p className="mb-2 text-sm text-muted-foreground">
                                        Moving to another chat?
                                    </p>
                                    {link === null ? (
                                        <LinkButton
                                            label="Connect a different chat"
                                            variant="outline"
                                        />
                                    ) : (
                                        <Button asChild variant="outline">
                                            <a
                                                href={link}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <Send />
                                                Open {botName}
                                            </a>
                                        </Button>
                                    )}
                                </div>
                            </Card>
                        ) : null}

                        <Card className="gap-3 p-5">
                            <CardTitle>Help</CardTitle>
                            <div className="divide-y">
                                {help.map((item) => (
                                    <details
                                        key={item.question}
                                        className="group py-1 [&_summary::-webkit-details-marker]:hidden"
                                    >
                                        <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-md text-sm font-medium outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
                                            {item.question}
                                            <ChevronDown
                                                className="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                                                aria-hidden="true"
                                            />
                                        </summary>
                                        <p className="mb-2 text-sm text-muted-foreground">
                                            {item.answer}
                                        </p>
                                    </details>
                                ))}
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

Telegram.layout = {
    breadcrumbs: [{ title: 'Telegram', href: vendor.telegram() }],
};
