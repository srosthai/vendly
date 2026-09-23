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
import { Reveal } from '@/components/marketing/reveal';
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

export default function Features({ meta }: { meta: PageMeta }) {
    return (
        <>
            <MarketingHead meta={meta} />
            <section className="px-4 pt-16 pb-6 md:px-6 lg:pt-24">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        as="h1"
                        title="Everything you need to sell from a link"
                        description="Vendly keeps selling simple: a store, a catalog, and requests that arrive in Telegram."
                    />
                </div>
            </section>
            {groups.map((group, groupIndex) => (
                <section
                    key={group.title}
                    className={
                        groupIndex % 2 === 0
                            ? 'px-4 py-14 md:px-6'
                            : 'bg-card px-4 py-14 md:px-6'
                    }
                >
                    <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[minmax(0,18rem)_1fr]">
                        <Reveal>
                            <h2 className="text-2xl font-bold tracking-tight">
                                {group.title}
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                {group.description}
                            </p>
                        </Reveal>
                        <ul className="grid gap-4 sm:grid-cols-2">
                            {group.items.map((item, index) => (
                                <Reveal
                                    as="li"
                                    key={item.title}
                                    delay={(index % 2) * 90}
                                    className={
                                        groupIndex % 2 === 0
                                            ? 'flex gap-4 rounded-2xl border bg-card p-5'
                                            : 'flex gap-4 rounded-2xl border bg-background p-5'
                                    }
                                >
                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                        <item.icon
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <h3 className="font-semibold">
                                            {item.title}
                                        </h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {item.body}
                                        </p>
                                    </div>
                                </Reveal>
                            ))}
                        </ul>
                    </div>
                </section>
            ))}
            <CtaBand />
        </>
    );
}
