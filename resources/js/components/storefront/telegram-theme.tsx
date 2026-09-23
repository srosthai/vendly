import { useEffect } from 'react';

type TelegramWebApp = {
    ready: () => void;
    expand: () => void;
    themeParams?: Record<string, string>;
};

export function useTelegramTheme(enabled: boolean): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const telegram = (
            window as Window & { Telegram?: { WebApp?: TelegramWebApp } }
        ).Telegram?.WebApp;

        if (!telegram) {
            return;
        }

        telegram.ready();
        telegram.expand();
        const root = document.documentElement;
        const theme = telegram.themeParams ?? {};

        if (theme.bg_color) {
            root.style.setProperty('--background', theme.bg_color);
        }

        if (theme.text_color) {
            root.style.setProperty('--foreground', theme.text_color);
        }

        if (theme.button_color) {
            root.style.setProperty('--primary', theme.button_color);
        }

        if (theme.button_text_color) {
            root.style.setProperty(
                '--primary-foreground',
                theme.button_text_color,
            );
        }
    }, [enabled]);
}
