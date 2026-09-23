# Vendly — simple multi-vendor shop on the web and in one Telegram mini app

Vendly is built on the Laravel React starter (Fortify, Inertia, Wayfinder, Pest). The first round (#2–#14) shipped accounts, stores, the catalog, the cart, Telegram requests, CutLuy plans, and the vendor and admin dashboards. The second round (#44) hardens that work and applies the Vendly brand. This plan is the product shape both rounds build to.

One platform. Many independent stores. A customer always shops inside one vendor’s store. The same store opens on the web and inside one Telegram mini app.

“Production” in the original notes is treated as **products**.

## Corrections that keep v1 small

These replace ideas that sound right but are hard to run, or that Telegram does not allow.

1. **One bot and one mini app for the whole platform.** Telegram registers a mini app on a single bot. A new mini app cannot be created automatically when a vendor opens a store. Each store is a link into that one app. The vendor pastes nothing. Vendly generates both links when the store is created:
    - Web: `https://{app-domain}/s/{slug}`
    - Telegram: `https://t.me/{bot}/{app}?startapp={slug}`
2. **Categories and brands belong to the store, and the vendor manages them.** Admin does not edit each vendor’s catalog. Admin manages vendors, plans, subscriptions, and the platform Telegram settings. A shared marketplace catalog (one category tree for every store) is a later product, because v1 customers arrive at a specific store.
3. **One store per account.** A vendor signs up, creates one store, and sells there. A second store per account waits.
4. **One user table for everyone.** There is no separate vendor account system and customer account system. A person is a customer until they open a store. Admin is a flag on the same user.
5. **Plans limit how many products a store may publish.** Example the admin can create: Free 10 products, Starter 100 products at $5/month. The limit counts **published** products. Drafts do not count. Admin creates and edits plans. A default free plan is assigned when the store is created, so signup is usable before any payment exists.
6. **Money is split into two different actions.**
    - A vendor pays for a plan inside Vendly with a Cambodia QR payment through CutLuy (USD only). The plan turns on only when CutLuy reports `payment.completed`.
    - A customer does not pay in the app. The store only displays products. Buy sends that product to Telegram. A cart sends several products in one message. The vendor’s Telegram and the platform admin Telegram both receive it. There is no order inbox, no status workflow, and no card checkout.
7. **Custom domains wait.** v1 stores live on the admin domain only. A later phase can map `shop.vendor.com` to the same store. The mini app still uses the platform domain, because Telegram must load one HTTPS app URL.
8. **Customer login depends on where they are.**
    - Web: email and password, or Google. Registering with email sends a one-time code, and the account is created only after the code is correct. Browsing stays open. Login is required at Buy.
    - Mini app: no Google and no email prompt. Telegram already identified them. Vendly checks Telegram’s signed `initData` and creates the customer from their Telegram id, name, and username.

## Who uses it

| Person   | What they can do in v1                                                                                                                                                                                |
| -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Admin    | See and suspend vendors. Create plans. See which stores paid through CutLuy. Set the bot token, bot username, CutLuy keys, and the admin Telegram chat.                                               |
| Vendor   | Register, create one store, manage that store’s categories, brands, and products, pay for a bigger plan by QR, share the web link and the mini app link, connect Telegram so buy requests reach them. |
| Customer | Open one store, browse its products, and — after signing in — send one product or a cart of products to Telegram.                                                                                     |

A vendor shopping at another store is just a customer there. Store data never leaks across vendors: every catalog query is scoped by `store_id`.

## Store links and the mini app

```
Vendor creates store "Smile Tea" with slug smile-tea
        │
        ├─ Web storefront
        │    https://vendly.example/s/smile-tea
        │
        └─ One platform mini app
             https://t.me/VendlyBot/shop?startapp=smile-tea
                       │
                       ▼
             Mini app opens https://vendly.example/m
             Reads startapp = smile-tea
             Verifies initData, signs the customer in
             Redirects to /s/smile-tea inside the same session
```

- The mini app URL is configured once, in BotFather, as `https://{app-domain}/m`.
- Opening the mini app with no store shows a short “open a store link from the seller” screen. v1 has no public directory of every vendor.
- The web store URL also works inside Telegram. If the page sees `window.Telegram.WebApp`, it runs the same silent sign-in.
- The server accepts Telegram identity only after it checks the `initData` hash with the bot token, and only if `auth_date` is fresh. The client-supplied user id is never trusted by itself.
- Telegram's script defines `window.Telegram.WebApp` in every browser, so the page only treats itself as inside Telegram when `initData` is not empty. It then posts `initData` with the current store path, follows Telegram's light or dark theme, and shows a short "Signing you in with Telegram" line, or the error with Try again.
- `auth_date` is fresh for `TELEGRAM_INIT_DATA_MAX_AGE` seconds (default one hour) and never in the future.
- Telegram Web runs the mini app in a cross-site iframe. Production sets `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=none`, and `SESSION_PARTITIONED_COOKIE=true` so the session cookie still reaches it. The phone and desktop apps load the page directly.

## Accounts

Two screens for web.

**Log in:** email and password, plus **Continue with Google**. Fortify owns the password check and the two-factor challenge. No route signs a person in with only an emailed code.

**Register:**

1. Visitor enters a name, an email, and a password. Vendly keeps them with a hashed 6-digit code for 10 minutes and emails the code. No user row yet. An email that already has an account is refused with a link to Log in.
2. Visitor enters the code. Attempts are counted atomically and rate limited by email and IP. A wrong code does not extend the expiry.
3. Only when the code matches is the user created, with `email_verified_at` set and that password, and signed in.
4. Send them back to the page they were trying to open (the product or cart they wanted to send, or “start selling”).

Fortify’s own `POST /register` is turned off, so nothing creates a user without the code.

Google path: Laravel Socialite. Only an email Google marks as verified is accepted. It creates or matches the user and skips the code. When it matches a row whose email was never verified, that row’s password is cleared first.

Telegram path, mini app only:

1. The page posts `initData` to `POST /auth/telegram`.
2. On a valid signature, find or create the user by `telegram_id`. Name comes from Telegram. Email and password stay empty.
3. `Auth::login()` uses the normal session. The mini app is your own HTTPS origin, so the session cookie is first-party.

After sign-in:

- No store yet, and they were sending a product or a cart → return to that store and send.
- No store yet, and they chose Start selling → create the store (name + slug), attach the free plan, notify the admin Telegram chat.
- They already own a store → vendor dashboard.

Fortify password reset, two-factor, and passkey screens stay. Web entry is email and password, or Google. Telegram-only customers have no email or password, so `users.email` and `users.password` are nullable, `MustVerifyEmail` must not block a user who signed in with Telegram, and settings never ask them for a password they do not have.

Seeded demo accounts exist for local and testing only. The seeder refuses to run in production.

## Plans, paid with CutLuy

Admin fields for a plan: name, monthly price in USD, published-product limit, active flag, default flag. A paid price is at least `0.01`. The default plan is free and never calls CutLuy.

Rules:

- Creating a store attaches the default plan. Its end date stays empty.
- Publishing a product is rejected when the published count is already at the limit. The vendor sees which plan they are on and how many products are left.
- Choosing a paid plan creates a local payment row, then a CutLuy payment. The vendor sees a QR dialog. The current plan stays in force until CutLuy sends `payment.completed`.
- `payment.scanned` means the customer opened the QR in a banking app. It does not start the plan.
- A completed payment starts or extends the plan by one month, from the later of now and the current end date. The same CutLuy payment id applied twice does not add a second month.
- An expired paid plan does not hide the store. It blocks new publishes and messages the vendor and the admin. Existing published products stay visible.
- Suspending a vendor hides the public store and blocks vendor writes.

Product prices on the store are display-only USD amounts inside the Telegram message. CutLuy is used for the vendor’s plan, not for the customer’s cart.

### CutLuy call

Secrets come from the environment, never from source:

- `CUTLUY_API_KEY` sent as `Authorization: Bearer …`
- `CUTLUY_WEBHOOK_SECRET` used only to verify webhooks
- `CUTLUY_BASE_URL` defaulting to `https://cutluy.com`

Create, from a queued job or a single request that does not retry in a loop:

`POST /v1/payments`

```json
{
    "amount": 5.0,
    "reference_id": "subpay_01J…",
    "metadata": { "store_id": 12, "plan_id": 3 },
    "idempotency_key": "subpay_01J…"
}
```

`reference_id` and `idempotency_key` are the local payment’s public id, so a double click or a retry returns the same CutLuy payment. Amount is a USD decimal. Currency is always USD.

The `201` body gives `id`, `status`, `checkout_url`, and `qr_string`. The plan dialog renders `qr_string` as a QR and offers `checkout_url` as a second way to pay. Local status starts as `pending`.

Error body shape is `{ "error": code, "message": "…" }`:

| HTTP | code                | What Vendly does                                                                                                                                         |
| ---- | ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 401  | `unauthorized`      | Log it, toast “Payments are unavailable”, message the admin chat. Do not show the raw API error to the vendor.                                           |
| 402  | `quota_exceeded`    | Same vendor-facing toast, distinct log, message the admin chat.                                                                                          |
| 403  | `account_suspended` | Same as 402.                                                                                                                                             |
| 429  | `rate_limited`      | Read `Retry-After`. Show “Try again in N seconds”. One delayed retry at that delay, then stop. Creates are limited to 60/minute and reads to 600/minute. |

The dialog does not poll `GET /v1/payments/:id` in a loop. The webhook is how Vendly learns the payment succeeded. A Refresh button may perform one read, and it also stops when CutLuy returns 429.

### CutLuy webhook

`POST /webhooks/cutluy`, CSRF excluded in `bootstrap/app.php`. No auth session.

Headers on every delivery: `X-CutLuy-Event` and `X-CutLuy-Signature: t=<unix>,v1=<hex>`.

Verify in this order, in PHP:

1. Read the signature header and the raw body with `$request->getContent()`. That string is the exact bytes CutLuy signed. Do not verify `json_encode($request->all())` or any re-serialized array. Re-encoding changes bytes and the signature will never match.
2. Split `t` and `v1`. Compute `hash_hmac('sha256', $t.'.'.$rawBody, $webhookSecret)`.
3. Compare with `hash_equals`, not `===`.
4. Reject when `t` is more than 5 minutes from now.
5. Only then decode the JSON.
6. Dispatch one queued job and return `204` immediately. CutLuy retries any non-2xx up to 8 times with backoff, so the HTTP handler does no Telegram calls and no subscription writes.

A delivery body is `{ "id", "type", "created", "data": { "payment": { "id", "status", "amount", "currency", "reference_id", ... } } }`. The top-level `id` names the event. The payment Vendly tracks is `data.payment.id`. The event comes from the signed `type` field, not from the unsigned `X-CutLuy-Event` header. Unknown event types return `204` and are not queued. A `payment.completed` only extends a plan when `reference_id`, `amount`, and `currency` match the local payment.

The job is idempotent on the CutLuy payment id plus the event name. A unique key records that this delivery was already applied. A second `payment.completed` for the same id is stored and ignored.

| Event               | Local status | Plan                         |
| ------------------- | ------------ | ---------------------------- |
| `payment.scanned`   | `scanned`    | unchanged                    |
| `payment.completed` | `paid`       | activate or extend one month |
| `payment.expired`   | `expired`    | unchanged                    |
| `payment.failed`    | `failed`     | unchanged                    |

`payment.completed` also messages the admin chat and the vendor chat. The other events update the payment row only.

## Catalog and buy requests

The vendor’s public store displays products. It does not take payment and it does not track fulfillment.

Vendor catalog, scoped to their store:

- Category: name, slug, sort.
- Brand: name, slug.
- Product: name, slug, description, display price in USD, optional stock note, category, brand, images, status draft or published.

Customer, on one store:

- List and open products. Filter by that store’s categories.
- Add to cart keeps a cart per user per store. A guest cart lives in the session and attaches to the user at sign-in. Adding to the cart does not send Telegram yet.
- Buy on a product, and Send on the cart, both require an account. Web users hit the Google / email-code wall and return to the same action. Mini app users are already signed in.
- Buy sends one product. Send on the cart sends every line in that cart as one message. Both go to the vendor chat when it is connected, and always to the platform admin chat.
- Each send stores an inquiry with the product name and price copied onto the lines, so a later price edit does not rewrite what was sent. The vendor does not get an orders screen. They read the request on Telegram. Admin can see the inquiry if a Telegram send failed and needs a retry.

Message shape:

```
New request — Smile Tea
From: Ada (@ada)
1 × Jasmine tea — $2.50
https://vendly.example/s/smile-tea/p/jasmine-tea
```

A cart lists every line and a total, then the store link. Stock, when the vendor set it to zero, hides Buy and Add to cart. Sending a request does not change stock.

## Telegram notifications

One bot. Two destinations.

| Event                           | Admin chat | Vendor chat                        |
| ------------------------------- | ---------- | ---------------------------------- |
| New store                       | yes        |                                    |
| Plan paid, or plan expired      | yes        | yes                                |
| Buy one product, or send a cart | yes        | yes, when the vendor has connected |

The vendor connects their chat from the dashboard. Vendly opens `https://t.me/{bot}?start=link_{one-time-token}`. The bot’s `/start` handler stores that chat id on the store. If they have not connected, the admin chat still receives the request, and the vendor is told in the dashboard that buy messages are only reaching the platform admin.

Platform settings (admin only, tokens encrypted): Telegram bot token, bot username, mini app short name, admin chat id, plus the CutLuy key and webhook secret from the environment.

## Data model

New tables, besides the existing Fortify users table:

- `users` gains nullable `email` and `password`, unique nullable `telegram_id`, nullable `telegram_username`, `is_admin`.
- `stores` — `user_id` unique, `name`, `slug` unique, description, logo, currency, `suspended_at`, `telegram_chat_id`.
- `plans` — name, price cents, `product_limit`, `is_active`, `is_default`.
- `subscriptions` — store, plan, status (`active`, `expired`, `canceled`), `starts_at`, `ends_at`.
- `subscription_payments` — public id, store, plan, amount, CutLuy id, status (`pending`, `scanned`, `paid`, `expired`, `failed`), `paid_at`. Unique on the CutLuy id.
- `cutluy_events` — CutLuy payment id plus event name, unique together, so a replay cannot extend a plan twice.
- `categories`, `brands` — store, name, slug unique per store.
- `products` — store, optional category, optional brand, name, slug unique per store, description, price cents, nullable stock, status.
- `product_images` — product, path, sort.
- `carts` — user, store, unique together. `cart_items` — product, quantity.
- `inquiries` — store, user, public number, customer name, contact (email or @username). `inquiry_items` — product, name snapshot, price cents, quantity. A flag records whether the admin chat and the vendor chat accepted the message.
- `otp_challenges` can live in the cache (email → hashed code, expiry, attempts) so unverified people never become rows.

Authorization:

- Admin routes require `is_admin`.
- Vendor routes require a store owned by the current user, and a store that is not suspended.
- Public store routes 404 when the store is missing or suspended.
- A vendor cannot read or write another store’s products, categories, or brands. Policies enforce `store_id`.
- The CutLuy webhook is unauthenticated on purpose. The signature check is the only gate. Failed checks return 401 and do not enqueue work.

## Interface

Every screen is built from the shadcn components already in this app (`components.json`: new-york, neutral, Lucide, CSS variables in `resources/css/app.css`). Primitives live in `resources/js/components/ui`. The kit already has button, input, input-otp, label, card, badge, dialog, sheet, dropdown menu, select, sidebar, breadcrumb, skeleton, sonner, spinner, avatar, alert, separator, and tooltip.

If a screen needs a primitive that is not there yet (table, tabs, textarea, switch, progress), add it with the shadcn CLI into `components/ui`. Do not invent a parallel button, input, modal, table, or toast.

### How components are split

Pages in `resources/js/pages` stay thin: layout, title, and a few feature components. The markup for a real piece of UI lives in one feature component under `resources/js/components/{auth,store,vendor,admin}/`.

Each feature component does one job. Examples: `OtpForm`, `StoreHeader`, `ProductCard`, `ProductGrid`, `CategoryPills`, `CartSheet`, `PlanUsage`, `PlanQrDialog`, `ShareLinks`. The web store and the mini app render the same storefront components. Only the chrome around them changes.

Match the screens that already exist: Inertia `<Form>`, Wayfinder form helpers, `Label`, `Input`, `InputError`, `Button`, and `Spinner` while a form submits. Colors come from the brand theme tokens (`bg-background`, `text-muted-foreground`, `border-border`, `bg-primary`), never from hex values in a component. Dark mode uses the `.dark` variables. Icons are Lucide.

A shipped page has no placeholder art, lorem, or a control that does nothing. The starter dashboard placeholder is replaced when the vendor dashboard exists.

### What “real UX” means on each surface

A screen is unfinished if it only handles the happy path. Empty, loading, validation, success, and blocked states are part of the component.

**Log in and register.** One card on the tinted page. Log in: email, password, Continue with Google, Forgot password, and a link to Register. Register: name, email, and password, then the existing OTP input, six digits, with the address shown and a resend countdown. The error sits under the field. The button shows a spinner while the request runs. Success returns the person to the product they wanted to send, or to Start selling.

**Vendor and admin.** Reuse the existing sidebar layout. Vendor items: Store, Products, Categories, Brands, Plan, Telegram. Admin items: Vendors, Plans, Payments, Telegram. Inner pages have a breadcrumb, a short heading, and one primary button (New product, New plan). Desktop lists use a table. Narrow screens use the same data as stacked cards. An empty list is a sentence plus that primary button. Delete and suspend open a dialog that names the record and uses a destructive button. Success and server failures use a sonner toast. Field errors stay inline. Plan usage reads as a real sentence, “12 of 100 published”, with a progress bar. When publish is blocked, the button stays visible and the nearby text says the plan is full. The plan page lists paid plans as cards. Pay opens a dialog with the QR, the amount in USD, a link to the CutLuy page, and a status line that can say Pending, Opened in banking app, or Paid. Scanned is never shown as paid.

**Storefront.** This is the screen customers judge. Header shows the store logo, name, and one-line description. Categories are a horizontal row of toggles that scroll on a phone. The product grid shows image, name, price, and a sold-out badge. The whole card is the link. Navigation shows a skeleton grid in the same shape as the cards. The product page has a large image, price, and description. On a phone, Add to cart and Buy sit in a sticky bar with padding for the safe area. On desktop they sit in a summary column. The cart is a sheet: each line with quantity and remove, then one Send button. A web guest who taps Buy or Send sees the sign-in card and returns to that action. Success is a toast: “Sent to the store on Telegram.”

**Mini app.** Same storefront components, less chrome. No marketing header. Full-width content, targets at least 44px, safe-area padding. When `Telegram.WebApp` exists, map its theme colors onto the existing CSS variables so the shop follows Telegram light or dark. Buy and Send stay shadcn buttons, so the web store does not depend on Telegram.

**Share links.** The store settings page shows the web URL and the `t.me` link in read-only inputs, each with Copy. Copy confirms with a toast. Connect Telegram is a real button that opens the bot link.

## How this gets built

The app stays Laravel 13, Inertia React, Fortify, Wayfinder, Pest, and Pint. New work follows the patterns already in the repo. It does not add Livewire, Sanctum, or a second way to sign in. Fortify already owns session auth. Domain steps that are reused or tested on their own go in `app/Actions`, next to the existing Fortify actions.

### Server

- Form Requests validate. Policies authorize. Controllers stay thin. `is_admin` is never fillable, and create/update uses `$request->validated()`.
- Status columns are backed enums. Lists eager-load relations and sort with a stable tie-break (`created_at`, then `id`).
- Publishing a product, and applying a paid CutLuy event, run inside a transaction with a row lock so two requests cannot slip past the product limit or add a second month.
- CutLuy and Telegram each have one client class, registered in the container. Application code reads `config()`, and `config/services.php` reads the environment. Keys are never written into source.
- Webhook handling, Telegram sends, and a delayed CutLuy retry are queued jobs with a retry limit, backoff, and a `failed()` log. The webhook route itself returns `204` before that work.
- Email codes and Telegram sign-in are rate limited by email or Telegram id plus IP.
- The CutLuy webhook and the Telegram bot webhook are the only CSRF exceptions, and they run without a session. CutLuy’s signature is checked with `hash_equals` over `$request->getContent()`. Telegram’s `X-Telegram-Bot-Api-Secret-Token` must match the configured secret. A missing secret or a failed check returns `401` and queues nothing.
- Product images allow jpeg, png, and webp, with a size cap, and are stored with a generated filename.
- Product text is rendered as text. Vendor HTML is not injected into the page.
- Each issue finishes with `vendor/bin/pint --dirty --format agent` and the focused Pest tests.

### Interface

Vendor and admin screens are for getting work done. A person should scan a list and finish one task. Those screens are not a marketing page.

The public store is a shop counter. The photograph and the price lead. Buy is the primary button. Add to cart is secondary. The web store and the mini app use the same components.

#### Brand

The logo is `public/vendly.png`: a blue V with an orange shopping cart above the navy word “Vendly”. The mark alone (V and cart) is the favicon, the home-screen icon, and the small logo in the sidebar. The app name is Vendly everywhere, including the page title and the mail sender.

| Token      | Value                          | Use                                                                                              |
| ---------- | ------------------------------ | ------------------------------------------------------------------------------------------------ |
| Blue       | `#0054D5`, hover `#0039B4`     | Primary buttons, links, focus ring, active navigation, and Buy.                                  |
| Light blue | `#0B77FB`                      | The lighter end of the mark, and soft tinted fills such as the active nav pill.                  |
| Orange     | `#FD890F` to `#FCA402`         | Accent only: cart count, highlight badges, and the sidebar promo card. Never body text on white. |
| Navy       | `#081A3B`                      | Text, headings, and dark mode surfaces. Text on orange is navy.                                  |
| Page       | a light blue-tinted near white | The page background, so white cards stand out.                                                   |

Buttons, text, and focus rings meet WCAG AA in light and dark.

#### Components

Every screen uses one component look, the shadcn primitives restyled in place:

- White cards with a large radius (about 20px) and a soft 1px border instead of a shadow, on the tinted page.
- Pill buttons. Primary is filled blue. Secondary is a bordered pill (for example “Weekly” or “Select dates”).
- Pill badges with soft tinted fills for status and category.
- A left sidebar with the logo, icon plus label items, and a filled soft-tinted pill for the active item. At the bottom sits one useful card, such as plan usage and Upgrade plan for a vendor.
- A top bar with a rounded search field, the page’s one primary action, notifications, and the avatar.
- Stat cards with a label, a value, and a small trend line. Tables with light header text that turn into stacked cards on a phone.

Do not introduce a cream-and-terracotta theme, a black page with one acid accent, or all-caps labels above every heading. Price is ordinary foreground text at a larger size.

Copy is sentence case and names the result: “Add to cart”, “Send to Telegram”, “Pay $5”, “Published”. An empty screen tells the viewer the one next step. An error says what failed and how to fix it, without an apology.

In the mini app, Telegram’s theme colors override the existing CSS variables. Tap targets are at least 44px, and the sticky bar keeps clear of the safe area. Every screen in an issue is checked at a desktop width and a narrow width.

## Issue sequence

The build is seven system issues. Code comes first. Each site follows from that backend. The responsive pass is last, and it checks desktop and mobile on every site. The behavior sections under the table are the rules those issues must include. This file is `docs/plan.md`.

| Order | Issue                                                                              | Depends on             |
| ----- | ---------------------------------------------------------------------------------- | ---------------------- |
| 1     | [#8 Code implement](https://github.com/srosthai/vendly/issues/8)                   | —                      |
| 2     | [#9 Frontend website](https://github.com/srosthai/vendly/issues/9)                 | #8                     |
| 3     | [#10 Admin dashboard](https://github.com/srosthai/vendly/issues/10)                | #8                     |
| 4     | [#11 Vendor dashboard](https://github.com/srosthai/vendly/issues/11)               | #8                     |
| 5     | [#12 Vendor frontend](https://github.com/srosthai/vendly/issues/12)                | #8                     |
| 6     | [#13 Customer site](https://github.com/srosthai/vendly/issues/13)                  | #8, #12                |
| 7     | [#14 Responsive: desktop and mobile](https://github.com/srosthai/vendly/issues/14) | #9, #10, #11, #12, #13 |

### 1. Accounts

Password login, email-code registration, Google, and Telegram identity on the existing user table.

Depends on: nothing.

- No user row exists before a valid registration code. Google sign-in skips the code. A bad or stale Telegram `initData` is rejected.
- `email` and `password` are nullable. `telegram_id` is unique. `is_admin` is not mass assignable.
- Rate limit the code request, the code check, and the Telegram sign-in. Regenerate the session when a sign-in succeeds.
- Log in is one card: email, password, plus Continue with Google. Register is name, email, and password, then the six-digit OTP input.

### 2. Store and the free plan

One store per account, a public URL, and a default free plan.

Depends on: Accounts.

- A second store for the same user is rejected. Slugs are unique.
- Creating a store attaches the default plan and messages the admin chat once that chat exists. Until the bot issue lands, the notification is a queued job that no-ops cleanly when the bot is not configured.
- `/s/{slug}` shows the store name and “No products yet”. A suspended store is hidden.
- The vendor reaches this from “Start selling” after sign-in.

### 3. Catalog within the plan limit

Categories, brands, products, and images for one store.

Depends on: Store and the free plan.

- Vendor A cannot change vendor B’s catalog. Policies scope every query by `store_id`.
- The 11th publish on a 10-product plan fails. Drafts do not count. The list shows “n of limit published”.
- Images are validated and stored as above. Lists use a table on desktop and cards on a narrow screen. Delete asks for confirmation in a dialog.
- The public grid and product page show only that store’s published products. Web and later mini app share these components.

### 4. Storefront cart

Add to cart and the cart sheet, still without sending Telegram.

Depends on: Catalog.

- A guest cart lives in the session and attaches to the user at sign-in. One cart per user per store.
- Sold-out products hide Buy and Add to cart.
- Buy and Send require an account. A web guest returns to the same action after the email code or Google sign-in. The buttons are visible. Sending is disabled with a short explanation until the Telegram issue is in place, so this issue does not pretend a message was sent.

### 5. Telegram requests and the mini app

One bot. Buy sends one product. The cart sends every line in one message.

Depends on: Storefront cart.

- Both messages go to the admin chat, and to the vendor chat when the vendor has connected it.
- Connecting Telegram uses a one-time `/start` token. The bot token and admin chat id come from config.
- Inquiry lines keep the name and price from the moment of sending.
- `/m` reads `startapp`, checks `initData`, and opens that store. A mini app user can send without an email.
- HTTP to Telegram is faked in tests.

### 6. Plan payment with CutLuy

Admin-managed plans. The vendor pays in USD by QR. The webhook turns the plan on.

Depends on: Catalog within the plan limit. Telegram messages for “plan paid” depend on issue 5; the payment itself can land first and queue the notice.

- Paid prices under $0.01 are rejected. The free plan never calls CutLuy.
- Create sends `reference_id` and `idempotency_key` as the local payment id. The dialog shows the QR and the hosted checkout link.
- The webhook verifies the raw body, rejects a timestamp older than five minutes, compares with `hash_equals`, returns `204`, and applies the event in a job.
- `payment.scanned` does not raise the product limit. `payment.completed` extends the plan by one month. The same payment id does not extend it twice.
- `401`, `402`, and `403` do not start a plan. `429` honors `Retry-After` once, then stops.
- The API key in tests comes from config. The HTTP client is faked.

## Build order

Build [#8](https://github.com/srosthai/vendly/issues/8) first. The site issues can start once the routes they need exist. [#14](https://github.com/srosthai/vendly/issues/14) is the last pass, at 1280px and at 390px. Stay on Inertia React pages, shadcn components, Wayfinder actions, Form Requests, policies, queued jobs, and Pest.

### Round 2: harden v1 and apply the brand

A review of the code against this plan and #2–#14 found security holes, rules the code did not enforce, and promised features that were never built. Round 2 is tracked in [#44](https://github.com/srosthai/vendly/issues/44). Security and correctness land before the redesign.

| Order | Issue                                                                                                      | Depends on         |
| ----- | ---------------------------------------------------------------------------------------------------------- | ------------------ |
| 1     | [#24 Plan: password login and the Vendly brand](https://github.com/srosthai/vendly/issues/24)              | —                  |
| 2     | [#25 Accounts: close the registration and sign-in bypasses](https://github.com/srosthai/vendly/issues/25)  | #24                |
| 3     | [#27 Webhooks: refuse deliveries when secrets are missing](https://github.com/srosthai/vendly/issues/27)   | —                  |
| 4     | [#28 Vendor area: one middleware for an owned, active store](https://github.com/srosthai/vendly/issues/28) | —                  |
| 5     | [#29 Storefront: only published products, rate limited](https://github.com/srosthai/vendly/issues/29)      | —                  |
| 6     | [#30 Plans: exactly one free, active default plan](https://github.com/srosthai/vendly/issues/30)           | —                  |
| 7     | [#31 CutLuy payments: one per click, 429, safe expiry](https://github.com/srosthai/vendly/issues/31)       | #30                |
| 8     | [#26 Mini app: Telegram initData sign-in](https://github.com/srosthai/vendly/issues/26)                    | #25                |
| 9     | [#32 Plan page: Paid, Refresh, and expiry](https://github.com/srosthai/vendly/issues/32)                   | #31                |
| 10    | [#33 Cart sheet: quantity and remove](https://github.com/srosthai/vendly/issues/33)                        | #29                |
| 11    | [#34 Catalog: edit and delete](https://github.com/srosthai/vendly/issues/34)                               | #28                |
| 12    | [#35 Stores: slug for Khmer names and Telegram links](https://github.com/srosthai/vendly/issues/35)        | —                  |
| 13    | [#36 Telegram requests: see and retry failed sends](https://github.com/srosthai/vendly/issues/36)          | #27                |
| 14    | [#37 Performance: indexes and pagination](https://github.com/srosthai/vendly/issues/37)                    | —                  |
| 15    | [#38 Brand: logo, favicon, name, colors](https://github.com/srosthai/vendly/issues/38)                     | #24                |
| 16    | [#39 Design system and app shell](https://github.com/srosthai/vendly/issues/39)                            | #38                |
| 17    | [#40 Auth screens](https://github.com/srosthai/vendly/issues/40)                                           | #25, #39           |
| 18    | [#41 Vendor and admin dashboards](https://github.com/srosthai/vendly/issues/41)                            | #32, #34, #36, #39 |
| 19    | [#42 Landing page, storefront, and mini app](https://github.com/srosthai/vendly/issues/42)                 | #26, #33, #39      |
| 20    | [#43 Final responsive and accessibility pass](https://github.com/srosthai/vendly/issues/43)                | #40, #41, #42      |

### Later, only after v1 is in use

- Charging the customer for products inside the app.
- Custom domain: `stores.custom_domain`, host middleware, TLS. Mini app links stay on the platform domain.
- More than one store per account, product variants, coupons, a public directory of stores.

## What v1 deliberately leaves out

Staff accounts for a vendor, an order inbox, product options and variants, coupons, reviews, customer card payments, shipping rates, tax, a cross-store marketplace home, a mini app or bot per vendor, and custom domains.

## Test and verification bar

Feature tests cover the rules above. Browser check walks the real screens on desktop and a narrow viewport: log in, the register code step, an empty store, a product grid and product page, Add to cart then Send, a guest stopped at Buy until the email code succeeds, the mini app entry opening the same storefront, a vendor hitting the plan limit, and the plan dialog showing a QR with Pending rather than Paid. Confirm the controls are the shadcn ones (button, input, OTP, dialog, sheet, table, toast), and that empty, loading, and error states are visible. Webhook checks stay in automated tests, because a browser cannot sign a CutLuy delivery.
