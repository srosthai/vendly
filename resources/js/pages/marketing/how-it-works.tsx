import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { Faq } from '@/components/marketing/faq';
import { Journey } from '@/components/marketing/journey';
import {
    PhoneMockup,
    RequestMessage,
} from '@/components/marketing/product-fragments';
import { Reveal } from '@/components/marketing/reveal';
import { CtaBand, SectionHeading } from '@/components/marketing/section';

const sellerSteps = [
    {
        title: 'Create your account',
        body: 'Register with your email and a password. We email a six-digit code to check the address. Google works too.',
        detail: 'You get a six-digit code by email',
    },
    {
        title: 'Open your store',
        body: 'Name the store, choose its link, and add a one-line description and your logo. The free plan is attached right away.',
        detail: 'Your link: /s/your-store',
    },
    {
        title: 'Add products',
        body: 'Add a name, price, photos, and optionally a category, brand, and stock. Products start as drafts.',
        detail: 'Saved as Draft',
    },
    {
        title: 'Publish',
        body: 'Publish the products that are ready. Your plan sets how many can be live at once.',
        detail: 'Draft becomes Published',
    },
    {
        title: 'Connect Telegram',
        body: 'Tap Connect Telegram and press Start in the Vendly bot, so requests reach your own chat.',
        detail: 'Status: Connected',
    },
    {
        title: 'Share your links',
        body: 'Copy your web link and your Telegram link from the dashboard and send them to customers.',
        detail: 'Web link and Telegram link, ready to copy',
    },
];

const customerSteps = [
    {
        title: 'Open the store link',
        body: 'Tap the link a seller shared, in a browser or in Telegram.',
        detail: 'No app to install',
    },
    {
        title: 'Pick products',
        body: 'Browse by category, open a product, and add it to your cart or tap Buy.',
        detail: 'Cart keeps quantities',
    },
    {
        title: 'Send the request',
        body: 'Inside Telegram you are signed in already. On the web, log in first.',
        detail: 'One tap: Send to Telegram',
    },
    {
        title: 'Agree with the seller',
        body: 'The seller gets your request in Telegram and replies to arrange payment and delivery.',
        detail: 'Payment and delivery, your way',
    },
];

const faq = [
    {
        question: 'Do customers pay inside Vendly?',
        answer: 'No. Vendly sends the request to the seller in Telegram, and the two of you agree on payment and delivery there. Vendly never handles the money from your sales.',
    },
    {
        question: 'What does a request look like?',
        answer: 'A numbered message in your Telegram chat with the customer’s name and contact, each product with its quantity and price, the total, and a link back to your store.',
    },
    {
        question: 'Do my customers need an account?',
        answer: 'Inside the Telegram mini app, no: Telegram already knows who they are. On the web, they log in or register before sending, so you always know who asked.',
    },
    {
        question: 'What happens when my plan is full?',
        answer: 'You can keep adding drafts, but publishing more waits until you upgrade or unpublish a product. Published products stay visible.',
    },
    {
        question: 'What if my paid plan ends?',
        answer: 'Your store stays online and published products stay visible. New products cannot be published until you pay for another month.',
    },
    {
        question: 'Can I use Vendly on my phone?',
        answer: 'Yes. The store, the cart, and your whole dashboard work on a phone, inside Telegram or in a browser.',
    },
];

export default function HowItWorks({ meta }: { meta: PageMeta }) {
    return (
        <>
            <MarketingHead meta={meta} />
            <section className="px-4 pt-16 pb-6 md:px-6 lg:pt-24">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        as="h1"
                        title="How Vendly works"
                        description="A seller opens a store and shares one link. Customers pick products and send a request. The seller answers in Telegram."
                    />
                </div>
            </section>
            <section className="px-4 py-14 md:px-6">
                <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:gap-16">
                    <div className="lg:sticky lg:top-24 lg:self-start">
                        <SectionHeading
                            title="For sellers"
                            description="From sign-up to your first request, usually the same day."
                        />
                        <Reveal
                            delay={120}
                            className="mt-10 hidden justify-center lg:flex"
                        >
                            <PhoneMockup />
                        </Reveal>
                    </div>
                    <Journey steps={sellerSteps} />
                </div>
            </section>
            <section className="bg-card px-4 py-14 md:px-6">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        title="For customers"
                        description="No app to install and no card to enter."
                    />
                    <div className="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:items-center lg:gap-16">
                        <Journey steps={customerSteps} tone="highlight" />
                        <Reveal delay={120} className="flex justify-center">
                            <RequestMessage className="w-full max-w-xs" />
                        </Reveal>
                    </div>
                </div>
            </section>
            <section className="px-4 py-16 md:px-6">
                <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[minmax(0,20rem)_1fr]">
                    <SectionHeading
                        title="Questions"
                        description="The things sellers ask most before they start."
                    />
                    <Faq items={faq} />
                </div>
            </section>
            <CtaBand />
        </>
    );
}
