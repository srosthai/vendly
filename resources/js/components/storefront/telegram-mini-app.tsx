import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import TelegramAuthController from '@/actions/App/Http/Controllers/Auth/TelegramAuthController';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/hooks/use-appearance';

type TelegramWebApp = {
    initData: string;
    colorScheme?: 'light' | 'dark';
    themeParams?: Record<string, string | undefined>;
    ready: () => void;
    expand: () => void;
    onEvent?: (event: 'themeChanged', handler: () => void) => void;
    offEvent?: (event: 'themeChanged', handler: () => void) => void;
};

/**
 * Telegram's script defines `WebApp` in every browser, so only a non-empty
 * `initData` means the page really runs inside Telegram.
 */
function telegramWebApp(): TelegramWebApp | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const webApp = (
        window as Window & { Telegram?: { WebApp?: TelegramWebApp } }
    ).Telegram?.WebApp;

    return webApp && webApp.initData !== '' ? webApp : null;
}

const themeVariables: Record<string, string[]> = {
    bg_color: ['--background'],
    text_color: ['--foreground', '--card-foreground'],
    secondary_bg_color: ['--card', '--muted', '--secondary'],
    hint_color: ['--muted-foreground'],
    button_color: ['--primary', '--ring'],
    button_text_color: ['--primary-foreground'],
    section_separator_color: ['--border', '--input'],
    destructive_text_color: ['--destructive'],
};

function applyTelegramTheme(webApp: TelegramWebApp): void {
    const root = document.documentElement;
    const dark = webApp.colorScheme === 'dark';
    root.classList.toggle('dark', dark);
    root.style.colorScheme = dark ? 'dark' : 'light';

    for (const [param, variables] of Object.entries(themeVariables)) {
        const color = webApp.themeParams?.[param];

        variables.forEach((variable) =>
            color
                ? root.style.setProperty(variable, color)
                : root.style.removeProperty(variable),
        );
    }
}

function clearTelegramTheme(): void {
    const root = document.documentElement;

    Object.values(themeVariables)
        .flat()
        .forEach((variable) => root.style.removeProperty(variable));
}

type MiniAppState = {
    inTelegram: boolean;
    signingIn: boolean;
    error: string | null;
    retry: () => void;
};

/**
 * Inside Telegram, follow Telegram's theme and sign a guest in with the
 * signed `initData`, then reload the same store page.
 */
export function useTelegramMiniApp(authenticated: boolean): MiniAppState {
    const [webApp] = useState(telegramWebApp);
    const { appearance } = useAppearance();
    const [signingIn, setSigningIn] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const signIn = useCallback(() => {
        if (!webApp) {
            return;
        }

        setError(null);
        router.post(
            TelegramAuthController.store.url(),
            {
                init_data: webApp.initData,
                redirect: window.location.pathname + window.location.search,
            },
            {
                preserveScroll: true,
                onStart: () => setSigningIn(true),
                onError: (errors) =>
                    setError(
                        errors.init_data ??
                            'Telegram sign-in failed. Try again.',
                    ),
                onFinish: () => setSigningIn(false),
            },
        );
    }, [webApp]);

    useEffect(() => {
        if (!webApp) {
            return;
        }

        webApp.ready();
        webApp.expand();
    }, [webApp]);

    /*
     * System means "follow Telegram": its light or dark scheme and colors,
     * live as Telegram changes them. Light or Dark picked in the theme
     * switch overrides Telegram and uses the Vendly colors.
     */
    useEffect(() => {
        if (!webApp) {
            return;
        }

        if (appearance !== 'system') {
            clearTelegramTheme();
            document.documentElement.classList.toggle(
                'dark',
                appearance === 'dark',
            );

            return;
        }

        const follow = () => {
            let stored: string | null = 'system';

            try {
                stored = localStorage.getItem('appearance');
            } catch {
                stored = 'system';
            }

            if ((stored ?? 'system') === 'system') {
                applyTelegramTheme(webApp);
            }
        };
        follow();
        webApp.onEvent?.('themeChanged', follow);
        window.addEventListener('appearance-applied', follow);

        return () => {
            webApp.offEvent?.('themeChanged', follow);
            window.removeEventListener('appearance-applied', follow);
        };
    }, [webApp, appearance]);

    useEffect(() => {
        if (webApp && !authenticated) {
            signIn();
        }
    }, [webApp, authenticated, signIn]);

    return { inTelegram: webApp !== null, signingIn, error, retry: signIn };
}

export function TelegramSignInNotice({
    signingIn,
    error,
    retry,
}: Pick<MiniAppState, 'signingIn' | 'error' | 'retry'>) {
    if (signingIn) {
        return (
            <p
                role="status"
                className="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <Spinner />
                Signing you in with Telegram
            </p>
        );
    }

    if (error) {
        return (
            <div
                role="alert"
                className="flex flex-wrap items-center gap-3 text-sm text-destructive"
            >
                <span>{error}</span>
                <Button size="sm" variant="outline" onClick={retry}>
                    Try again
                </Button>
            </div>
        );
    }

    return null;
}
