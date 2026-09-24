import { Form, Head, usePoll } from '@inertiajs/react';
import { Check, Send, Unlink, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import TelegramLinkController from '@/actions/App/Http/Controllers/TelegramLinkController';
import { ConfirmActionDialog } from '@/components/confirm-action-dialog';
import { Disclosure } from '@/components/disclosure';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import vendor from '@/routes/vendor';

type Chat = {
    name: string | null;
    group: boolean;
    connected_at: string | null;
};

type Links = { chat: string; group: string };

type TestResult = { type: 'success' | 'error'; message: string };

const help = [
    {
        question: 'How do I get requests in a group?',
        answer: 'Tap Add to a group, pick the group in Telegram, and confirm adding the bot. The bot needs to stay in the group to deliver requests.',
    },
    {
        question: 'I tapped Start, but this page still says waiting',
        answer: 'The link works for 15 minutes and only once. Tap Get a new link, open the bot again, and tap Start.',
    },
    {
        question: 'The test says I blocked the bot',
        answer: 'Open the Vendly bot in Telegram and tap Restart or Unblock, then send the test again.',
    },
    {
        question: 'I want requests somewhere else',
        answer: 'Tap Connect a different chat and choose your own chat or a group. The old chat stops getting requests as soon as the new one connects. Disconnect stops them without a new chat.',
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

/**
 * The two ways to connect with one code: the vendor's own chat, or a group
 * Telegram lets them pick (it adds the bot there).
 */
function ChatChoices({ links, botName }: { links: Links; botName: string }) {
    return (
        <div className="grid gap-3">
            <div className="flex flex-wrap gap-2">
                <Button asChild>
                    <a href={links.chat} target="_blank" rel="noreferrer">
                        <Send />
                        My own chat
                    </a>
                </Button>
                <Button asChild variant="outline">
                    <a href={links.group} target="_blank" rel="noreferrer">
                        <Users />
                        Add to a group
                    </a>
                </Button>
            </div>
            <p className="text-sm text-muted-foreground">
                My own chat opens {botName}; tap Start. Add to a group lets you
                pick the group, then adds the bot there. Either link works for
                15 minutes.
            </p>
        </div>
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
    link: Links | null;
    testResult: TestResult | null;
}) {
    // When moving to another chat, wait until the connection changes.
    const [connectedWhenLinked] = useState(chat?.connected_at ?? null);
    const waiting =
        link !== null &&
        (!connected || chat?.connected_at === connectedWhenLinked);
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
    const chatLabel = chat
        ? `${chat.group ? 'Group' : 'Chat'}: ${chat.name ?? (chat.group ? 'your group' : 'your Telegram chat')}`
        : '';

    return (
        <>
            <Head title="Telegram" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Telegram"
                    description="Buy requests from your store arrive in your Telegram chat or group."
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
                                    ? chat?.group
                                        ? 'Your group is connected'
                                        : 'Your chat is connected'
                                    : 'Connect Telegram in three steps'}
                            </CardTitle>
                            <CardDescription className="mt-1">
                                {connected
                                    ? `Every buy request and cart reaches this ${chat?.group ? 'group' : 'chat'}. The Vendly admin keeps a copy too.`
                                    : 'Until you connect, buy requests only reach the Vendly admin.'}
                            </CardDescription>
                        </div>

                        {connected && !waiting ? (
                            <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border bg-background p-4">
                                <div className="flex min-w-0 items-center gap-3">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-success/12 text-success">
                                        {chat?.group ? (
                                            <Users
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <Send
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        )}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {chatLabel}
                                        </p>
                                        {chat?.connected_at ? (
                                            <p className="text-sm text-muted-foreground">
                                                Since{' '}
                                                {formatDateTime(
                                                    chat.connected_at,
                                                )}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <LinkButton
                                        label="Connect a different chat"
                                        variant="outline"
                                    />
                                    <ConfirmActionDialog
                                        title="Disconnect Telegram?"
                                        description={`Buy requests stop reaching ${chat?.group ? 'this group' : 'this chat'} and only go to the Vendly admin until you connect again. The ${chat?.group ? 'group' : 'chat'} gets a short note.`}
                                        confirmLabel="Disconnect"
                                        action={TelegramLinkController.destroy.form()}
                                        trigger={
                                            <Button
                                                variant="ghost"
                                                className="text-destructive"
                                            >
                                                <Unlink />
                                                Disconnect
                                            </Button>
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <ol>
                                <Step
                                    number={1}
                                    state={link !== null ? 'done' : 'current'}
                                    title="Get your connection link"
                                >
                                    {link === null ? (
                                        <LinkButton label="Get my link" />
                                    ) : null}
                                </Step>
                                <Step
                                    number={2}
                                    state={
                                        link !== null ? 'current' : 'upcoming'
                                    }
                                    title="Choose where requests go"
                                >
                                    {link !== null ? (
                                        <ChatChoices
                                            links={link}
                                            botName={botName}
                                        />
                                    ) : null}
                                </Step>
                                <Step
                                    number={3}
                                    last
                                    state="upcoming"
                                    title="See Connected here"
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
                                            Waiting for Telegram. This page
                                            updates on its own.
                                        </p>
                                    ) : null}
                                </Step>
                            </ol>
                        )}

                        {waiting ? (
                            <div className="flex flex-wrap gap-2 border-t pt-5">
                                <LinkButton
                                    label="Get a new link"
                                    variant="outline"
                                />
                                {connected ? (
                                    <p className="self-center text-sm text-muted-foreground">
                                        Requests keep going to {chatLabel} until
                                        the new one connects.
                                    </p>
                                ) : null}
                            </div>
                        ) : null}
                    </Card>

                    <div className="flex flex-col gap-6">
                        {connected ? (
                            <Card className="gap-4 p-5">
                                <div>
                                    <CardTitle>Check it works</CardTitle>
                                    <CardDescription className="mt-1">
                                        Send a test to your{' '}
                                        {chat?.group ? 'group' : 'chat'} now.
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
                            </Card>
                        ) : null}

                        <Card className="gap-3 p-5">
                            <CardTitle>Help</CardTitle>
                            <div className="divide-y">
                                {help.map((item) => (
                                    <Disclosure
                                        key={item.question}
                                        summary={item.question}
                                        className="py-1"
                                        summaryClassName="text-sm font-medium"
                                    >
                                        <p className="pb-2 text-sm text-muted-foreground">
                                            {item.answer}
                                        </p>
                                    </Disclosure>
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
