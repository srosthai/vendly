import AppLogoIcon from '@/components/app-logo-icon';

/**
 * The mark and the wordmark. The wordmark is live text in the foreground
 * color, so it stays readable in dark mode where the navy artwork would not.
 * In the collapsed sidebar only the mark shows.
 */
export default function AppLogo() {
    return (
        <>
            <AppLogoIcon className="size-8" />
            <span className="ml-1 truncate text-lg leading-none font-bold tracking-tight text-foreground group-data-[collapsible=icon]:hidden">
                Vendly
            </span>
        </>
    );
}
