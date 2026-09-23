import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

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
            <div className="flex max-w-lg flex-col gap-4 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Telegram
                </h1>
                <p className="text-sm text-muted-foreground">
                    {connected
                        ? 'Buy requests reach your Telegram chat.'
                        : 'Buy requests are only reaching the platform admin until you connect.'}
                </p>
                <Form action="/telegram/link" method="post">
                    {({ processing }) => (
                        <Button type="submit" disabled={processing}>
                            Connect Telegram
                        </Button>
                    )}
                </Form>
                {link ? (
                    <Button variant="outline" asChild>
                        <a href={link}>Open the bot</a>
                    </Button>
                ) : null}
            </div>
        </>
    );
}
