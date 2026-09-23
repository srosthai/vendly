import type { LucideIcon } from 'lucide-react';
import {
    BadgeCheck,
    Bell,
    Boxes,
    Camera,
    ChartNoAxesColumn,
    Hash,
    Link2,
    MessageSquareText,
    Moon,
    QrCode,
    Search,
    Send,
    ShieldCheck,
    ShoppingCart,
    Smartphone,
    Tags,
} from 'lucide-react';
import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { FeatureShowcase } from '@/components/marketing/feature-showcase';
import {
    PlanUsageFragment,
    PublishFragment,
    RequestMessage,
    ShareLinkFragment,
} from '@/components/marketing/product-fragments';
import { CtaBand, SectionHeading } from '@/components/marketing/section';

type Feature = { icon: LucideIcon; title: string; body: string };

const groups: { title: string; description: string; items: Feature[] }[] = [
    {
        title: 'Your store',
        description: 'One store per seller, open to anyone with the link.',
        items: [
            {
                icon: Link2,
                title: 'A web store and a mini app',
                body: 'Every store gets a web address and a Telegram mini app link. Both open the same products.',
            },
            {
                icon: Tags,
                title: 'Your own link',
                body: 'Choose a short link for your store. Names in Khmer or any language work too.',
            },
            {
                icon: Camera,
                title: 'Logo and photos',
                body: 'Add your store logo and several photos per product, with a cover photo you choose.',
            },
            {
                icon: Moon,
                title: 'Light and dark',
                body: 'The store follows each customer’s theme, and Telegram’s colors inside the mini app.',
            },
        ],
    },
    {
        title: 'Your catalog',
        description: 'Keep products tidy and publish only what is ready.',
        items: [
            {
                icon: Boxes,
                title: 'Categories and brands',
                body: 'Group products so customers can filter the store by category.',
            },
            {
                icon: BadgeCheck,
                title: 'Drafts and publishing',
                body: 'New products start as drafts. Publish them when the photos and price are right.',
            },
            {
                icon: Hash,
                title: 'Stock',
                body: 'Track stock if you want. A product with none left shows as sold out.',
            },
            {
                icon: Search,
                title: 'Search your products',
                body: 'Find any product in your dashboard in a second.',
            },
        ],
    },
    {
        title: 'Requests, not checkout',
        description:
            'Customers tell you what they want. You sell in Telegram, the way you already do.',
        items: [
            {
                icon: ShoppingCart,
                title: 'Buy or send a cart',
                body: 'Customers send one product, or a whole cart with quantities and a total.',
            },
            {
                icon: MessageSquareText,
                title: 'Straight to your chat',
                body: 'Each request arrives in your Telegram chat with a number, the products, and the customer’s contact.',
            },
            {
                icon: Send,
                title: 'Sign-in inside Telegram',
                body: 'In the mini app, Telegram already knows the customer, so there is no email to type.',
            },
            {
                icon: Bell,
                title: 'Nothing gets lost',
                body: 'Every request also reaches the Vendly admin, who can send it again if a chat was not connected.',
            },
        ],
    },
    {
        title: 'Plans and your dashboard',
        description: 'Simple plans, and a dashboard that works on your phone.',
        items: [
            {
                icon: QrCode,
                title: 'Pay by Cambodia QR',
                body: 'Upgrade by scanning a QR in your banking app. The plan turns on once the payment is confirmed.',
            },
            {
                icon: ChartNoAxesColumn,
                title: 'A clear overview',
                body: 'See published products, drafts, requests this week, and how much room your plan has left.',
            },
            {
                icon: Smartphone,
                title: 'Made for the phone',
                body: 'Manage products and read requests from a small screen.',
            },
            {
                icon: ShieldCheck,
                title: 'Your store stays yours',
                body: 'Other sellers can never see or change your products, categories, or brands.',
            },
        ],
    },
];

const visuals = [
    <ShareLinkFragment key="store" />,
    <PublishFragment key="catalog" />,
    <RequestMessage key="requests" className="mx-auto w-full" />,
    <PlanUsageFragment key="plans" />,
];

export default function Features({ meta }: { meta: PageMeta }) {
    return (
        <>
            <MarketingHead meta={meta} />
            <section className="px-4 pt-16 pb-4 md:px-6 lg:pt-24">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        as="h1"
                        title="Everything you need to sell from a link"
                        description="Vendly keeps selling simple: a store, a catalog, and requests that arrive in Telegram."
                    />
                </div>
            </section>
            <div className="flex flex-col gap-24 px-4 py-16 md:px-6 lg:gap-32">
                {groups.map((group, index) => (
                    <section
                        key={group.title}
                        className="mx-auto w-full max-w-6xl"
                    >
                        <FeatureShowcase
                            title={group.title}
                            description={group.description}
                            items={group.items}
                            visual={visuals[index]}
                            flip={index % 2 === 1}
                        />
                    </section>
                ))}
            </div>
            <CtaBand />
        </>
    );
}
