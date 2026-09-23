import { Head } from '@inertiajs/react';

export type PageMeta = { title: string; description: string; url: string };

/**
 * Title, description, canonical link, and Open Graph tags for a marketing
 * page. The server renders the same tags on first load; these keep them
 * right as people move between pages.
 */
export function MarketingHead({ meta }: { meta: PageMeta }) {
    return (
        <Head title={meta.title}>
            <meta
                head-key="description"
                name="description"
                content={meta.description}
            />
            <link head-key="canonical" rel="canonical" href={meta.url} />
            <meta
                head-key="og:title"
                property="og:title"
                content={`${meta.title} - Vendly`}
            />
            <meta
                head-key="og:description"
                property="og:description"
                content={meta.description}
            />
            <meta head-key="og:url" property="og:url" content={meta.url} />
        </Head>
    );
}
