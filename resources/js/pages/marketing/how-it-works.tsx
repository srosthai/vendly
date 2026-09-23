import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { Faq } from '@/components/marketing/faq';
import { Reveal } from '@/components/marketing/reveal';
import { CtaBand, SectionHeading } from '@/components/marketing/section';

const sellerSteps = [
    {
        title: 'Create your account',
        body: 'Register with your email and a password. We email a six-digit code to check the address. Google works too.',
    },
    {
        title: 'Open your store',
        body: 'Name the store, choose its link, and add a one-line description and your logo. The free plan is attached right away.',
    },
    {
        title: 'Add products',
        body: 'Add a name, price, photos, and optionally a category, brand, and stock. Products start as drafts.',
    },
    {
        title: 'Publish',
        body: 'Publish the products that are ready. Your plan sets how many can be live at once.',
    },
    {
        title: 'Connect Telegram',
        body: 'Tap Connect Telegram and press Start in the Vendly bot, so requests reach your own chat.',
    },
    {
        title: 'Share your links',
        body: 'Copy your web link and your Telegram link from the dashboard and send them to customers.',
    },
];

const customerSteps = [
    {
        title: 'Open the store link',
        body: 'Tap the link a seller shared, in a browser or in Telegram.',
    },
    {
        title: 'Pick products',
        body: 'Browse by category, open a product, and add it to your cart or tap Buy.',
    },
    {
        title: 'Send the request',
        body: 'Inside Telegram you are signed in already. On the web, log in first.',
    },
    {
        title: 'Agree with the seller',
        body: 'The seller gets your request in Telegram and replies to arrange payment and delivery.',
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

function Steps({
    title,
    description,
    steps,
}: {
    title: string;
    description: string;
    steps: { title: string; body: string }[];
}) {
    return (
        <div className="mx-auto max-w-6xl">
            <SectionHeading title={title} description={description} />
            <ol className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {steps.map((step, index) => (
                    <Reveal
                        as="li"
                        key={step.title}
                        delay={(index % 3) * 100}
                        className="flex flex-col gap-3 rounded-2xl border bg-card p-6"
                    >
                        <span
                            className="flex size-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                            aria-hidden="true"
                        >
                            {index + 1}
                        </span>
                        <h3 className="text-lg font-semibold">{step.title}</h3>
                        <p className="text-muted-foreground">{step.body}</p>
                    </Reveal>
                ))}
            </ol>
        </div>
    );
}

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
                <Steps
                    title="For sellers"
                    description="From sign-up to your first request."
                    steps={sellerSteps}
                />
            </section>
            <section className="bg-card px-4 py-14 md:px-6">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        title="For customers"
                        description="No app to install and no card to enter."
                    />
                    <ol className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {customerSteps.map((step, index) => (
                            <Reveal
                                as="li"
                                key={step.title}
                                delay={index * 100}
                                className="flex flex-col gap-3 rounded-2xl border bg-background p-6"
                            >
                                <span
                                    className="flex size-10 items-center justify-center rounded-full bg-highlight text-sm font-bold text-highlight-foreground"
                                    aria-hidden="true"
                                >
                                    {index + 1}
                                </span>
                                <h3 className="text-lg font-semibold">
                                    {step.title}
                                </h3>
                                <p className="text-muted-foreground">
                                    {step.body}
                                </p>
                            </Reveal>
                        ))}
                    </ol>
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
