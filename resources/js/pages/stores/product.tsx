import { Form, Head, Link } from '@inertiajs/react';
import { ChevronLeft, Package, Send, ShoppingBag } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import CartController from '@/actions/App/Http/Controllers/CartController';
import InquiryController from '@/actions/App/Http/Controllers/InquiryController';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import InputError from '@/components/input-error';
import { CartSheet, type CartData } from '@/components/storefront/cart-sheet';
import { StoreHeader } from '@/components/storefront/store-header';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import {
    TelegramSignInNotice,
    useTelegramMiniApp,
} from '@/components/storefront/telegram-mini-app';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dollars } from '@/lib/format';
import { cn } from '@/lib/utils';
import { login } from '@/routes';

type ProductProps = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    price_cents: number;
    sold_out: boolean;
    telegram_url: string | null;
    images: string[];
};

type StoreProps = {
    name: string;
    slug: string;
    url: string;
    logo: string | null;
};

function Gallery({ product }: { product: ProductProps }) {
    const [active, setActive] = useState(0);
    const current = product.images[active];

    return (
        <div className="flex flex-col gap-3">
            <div className="flex aspect-square items-center justify-center overflow-hidden rounded-3xl border bg-card">
                {current ? (
                    <img
                        src={current}
                        alt={
                            product.images.length > 1
                                ? `${product.name}, photo ${active + 1} of ${product.images.length}`
                                : product.name
                        }
                        className="size-full object-cover"
                    />
                ) : (
                    <Package
                        className="size-12 text-muted-foreground"
                        aria-hidden="true"
                    />
                )}
            </div>
            {product.images.length > 1 ? (
                <div
                    className="flex gap-2 overflow-x-auto"
                    role="group"
                    aria-label="Photos"
                >
                    {product.images.map((image, index) => (
                        <button
                            key={image}
                            type="button"
                            onClick={() => setActive(index)}
                            aria-label={`Show photo ${index + 1}`}
                            aria-pressed={index === active}
                            className={cn(
                                'size-16 shrink-0 overflow-hidden rounded-xl border-2 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                index === active
                                    ? 'border-primary'
                                    : 'border-transparent opacity-70 hover:opacity-100',
                            )}
                        >
                            <img
                                src={image}
                                alt=""
                                className="size-full object-cover"
                            />
                        </button>
                    ))}
                </div>
            ) : null}
        </div>
    );
}

/**
 * Add to cart and Buy. On a phone they sit in a bar fixed above the safe
 * area; on a wide screen they sit in the summary column. On the web, Buy
 * opens this product in the Telegram mini app, where the request is sent.
 */
function Actions({
    store,
    product,
    authenticated,
    inTelegram,
}: {
    store: StoreProps;
    product: ProductProps;
    authenticated: boolean;
    inTelegram: boolean;
}) {
    const args = { store: store.slug, product: product.id };

    return (
        <div className="fixed inset-x-0 bottom-0 z-20 border-t bg-card/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-md md:static md:z-auto md:border-0 md:bg-transparent md:p-0 md:backdrop-blur-none">
            <div className="mx-auto flex max-w-5xl gap-2">
                <Form
                    {...CartController.store.form(args)}
                    options={{ preserveScroll: true }}
                    className="flex-1"
                >
                    {({ processing, errors }) => (
                        <>
                            <Button
                                type="submit"
                                variant="outline"
                                size="lg"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? <Spinner /> : <ShoppingBag />}
                                Add to cart
                            </Button>
                            <InputError message={errors.product} />
                        </>
                    )}
                </Form>
                {!inTelegram && product.telegram_url ? (
                    <Button asChild size="lg" className="flex-1">
                        <a
                            href={product.telegram_url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Send />
                            Buy in Telegram
                        </a>
                    </Button>
                ) : authenticated ? (
                    <Form
                        {...InquiryController.product.form(args)}
                        options={{ preserveScroll: true }}
                        className="flex-1"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                size="lg"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? <Spinner /> : <Send />}
                                Buy
                            </Button>
                        )}
                    </Form>
                ) : (
                    <Button asChild size="lg" className="flex-1">
                        <Link
                            href={login({
                                query: {
                                    next: `/s/${store.slug}/p/${product.slug}`,
                                },
                            })}
                        >
                            <Send />
                            Buy
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}

export default function Product({
    store,
    product,
    embedded,
    authenticated,
    status,
    cart,
}: {
    store: StoreProps;
    embedded: boolean;
    authenticated: boolean;
    status?: string | null;
    cart: CartData;
    product: ProductProps;
}) {
    const miniApp = useTelegramMiniApp(authenticated);
    const inTelegram = embedded || miniApp.inTelegram;
    const buyInTelegram = !inTelegram && product.telegram_url !== null;

    useEffect(() => {
        if (!status) {
            return;
        }

        if (status.startsWith('Too many')) {
            toast.error(status);
        } else {
            toast.success(status);
        }
    }, [status]);

    return (
        <>
            <Head title={`${product.name} from ${store.name}`}>
                {product.description ? (
                    <meta
                        name="description"
                        content={product.description.slice(0, 160)}
                    />
                ) : null}
            </Head>
            <main className="mx-auto flex min-h-dvh w-full max-w-5xl flex-col gap-6 px-4 py-4 pb-28 sm:py-6 md:px-6 md:pb-6">
                <TelegramSignInNotice {...miniApp} />
                <StoreHeader
                    compact
                    store={store}
                    cart={
                        <CartSheet
                            cart={cart}
                            storeSlug={store.slug}
                            authenticated={authenticated}
                        />
                    }
                />
                <Link
                    href={StoreController.show(store.slug)}
                    className="-mt-2 inline-flex items-center gap-1 self-start rounded-md text-sm text-muted-foreground hover:text-foreground"
                >
                    <ChevronLeft className="size-4" aria-hidden="true" />
                    All products
                </Link>
                <div className="grid gap-6 md:grid-cols-2 md:gap-10">
                    <Gallery product={product} />
                    <div className="flex flex-col gap-4 md:sticky md:top-6 md:self-start">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                {product.name}
                            </h1>
                            <p className="mt-3 text-3xl font-bold tabular-nums">
                                {dollars(product.price_cents)}
                            </p>
                        </div>
                        {product.sold_out ? (
                            <Badge variant="secondary" className="self-start">
                                Sold out
                            </Badge>
                        ) : null}
                        {product.description ? (
                            <p className="max-w-prose leading-relaxed whitespace-pre-line text-muted-foreground">
                                {product.description}
                            </p>
                        ) : null}
                        {product.sold_out ? (
                            <p className="text-sm text-muted-foreground">
                                This product is sold out. Other products in the
                                store may still be available.
                            </p>
                        ) : (
                            <>
                                <Actions
                                    store={store}
                                    product={product}
                                    authenticated={authenticated}
                                    inTelegram={inTelegram}
                                />
                                <p className="text-sm text-muted-foreground">
                                    {buyInTelegram
                                        ? 'Buy opens this product in the Vendly mini app in Telegram, where you send it to the store. You agree on payment and delivery with the seller there.'
                                        : 'Buy sends this product to the store on Telegram. You agree on payment and delivery with the seller there.'}
                                </p>
                            </>
                        )}
                    </div>
                </div>
                <StorefrontFooter hidden={inTelegram} />
            </main>
        </>
    );
}
