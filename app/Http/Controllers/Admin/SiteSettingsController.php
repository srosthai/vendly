<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePaymentMethodRequest;
use App\Http\Requests\Admin\UpdateCutluySettingsRequest;
use App\Http\Requests\Admin\UpdateGoogleSettingsRequest;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Models\PaymentMethod;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What the public website footer shows (contact details, social links, and
 * the payment methods listed under "We accept"), the CutLuy credentials plan
 * payments use, and Google sign-in.
 */
class SiteSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = PlatformSetting::current();

        return Inertia::render('admin/site', [
            'settings' => [
                'company_name' => $settings->company_name ?? '',
                'address' => $settings->address ?? '',
                'phone' => $settings->phone ?? '',
                'email' => $settings->email ?? '',
                'footer_text' => $settings->footer_text ?? '',
                'social_links' => collect(PlatformSetting::SocialNetworks)
                    ->mapWithKeys(fn (string $network): array => [$network => $settings->social_links[$network] ?? ''])
                    ->all(),
            ],
            'cutluy' => [
                'api_key' => $this->secretState($settings->cutluy_api_key, (string) config('services.cutluy.key')),
                'webhook_secret' => $this->secretState($settings->cutluy_webhook_secret, (string) config('services.cutluy.webhook_secret')),
                'base_url' => $settings->cutluy_base_url ?? '',
                'default_base_url' => (string) config('services.cutluy.base_url'),
                'webhook_url' => route('webhooks.cutluy'),
            ],
            'google' => [
                'enabled' => $settings->google_enabled ?? true,
                'client_id' => $settings->google_client_id ?? '',
                'env_client_id' => filled(config('services.google.client_id')),
                'client_secret' => $this->secretState($settings->google_client_secret, (string) config('services.google.client_secret')),
                'redirect_url' => $settings->googleRedirectUrl(),
                'ready' => $settings->googleSignInReady(),
            ],
            'paymentMethods' => PaymentMethod::query()
                ->orderBy('sort')
                ->orderBy('id')
                ->get()
                ->map(fn (PaymentMethod $method): array => [
                    'id' => $method->id,
                    'name' => $method->name,
                    'sort' => $method->sort,
                    'logo' => $method->logoUrl(),
                ]),
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        PlatformSetting::current()->update([
            'company_name' => $validated['company_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'footer_text' => isset($validated['footer_text']) ? strip_tags($validated['footer_text']) : null,
            'social_links' => array_filter($validated['social_links'] ?? []),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Site settings saved.']);

        return back();
    }

    public function updateCutluy(UpdateCutluySettingsRequest $request): RedirectResponse
    {
        $settings = PlatformSetting::current();

        foreach (['api_key' => 'cutluy_api_key', 'webhook_secret' => 'cutluy_webhook_secret'] as $field => $column) {
            if ($request->boolean('clear_'.$field)) {
                $settings->{$column} = null;
            } elseif ($request->filled($field)) {
                $settings->{$column} = $request->string($field)->trim()->toString();
            }
        }

        $settings->cutluy_base_url = $request->filled('base_url') ? rtrim($request->string('base_url')->toString(), '/') : null;
        $settings->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'CutLuy settings saved.']);

        return back();
    }

    public function updateGoogle(UpdateGoogleSettingsRequest $request): RedirectResponse
    {
        $settings = PlatformSetting::current();
        $settings->google_enabled = $request->boolean('enabled');
        $settings->google_client_id = $request->filled('client_id') ? $request->string('client_id')->trim()->toString() : null;

        if ($request->boolean('clear_client_secret')) {
            $settings->google_client_secret = null;
        } elseif ($request->filled('client_secret')) {
            $settings->google_client_secret = $request->string('client_secret')->trim()->toString();
        }

        $settings->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Google sign-in settings saved.']);

        return back();
    }

    public function storePaymentMethod(SavePaymentMethodRequest $request): RedirectResponse
    {
        $this->fillPaymentMethod(new PaymentMethod, $request);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment method added.']);

        return back();
    }

    public function updatePaymentMethod(SavePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->fillPaymentMethod($paymentMethod, $request);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment method saved.']);

        return back();
    }

    public function destroyPaymentMethod(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment method removed.']);

        return back();
    }

    private function fillPaymentMethod(PaymentMethod $method, SavePaymentMethodRequest $request): void
    {
        $validated = $request->validated();
        $oldLogo = $method->logo_path;

        $method->name = strip_tags($validated['name']);
        $method->sort = (int) ($validated['sort'] ?? 0);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('payment-methods', 'public');

            if ($path === false) {
                throw ValidationException::withMessages(['logo' => 'The logo could not be saved. Try uploading it again.']);
            }

            $method->logo_path = $path;
        } elseif ($request->boolean('remove_logo')) {
            $method->logo_path = null;
        }

        $method->save();

        if ($oldLogo !== null && $oldLogo !== $method->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }
    }

    /**
     * What the form may know about a secret: where it comes from and its
     * last four characters, never the value itself.
     *
     * @return array{source: 'admin'|'env'|null, ends_with: string|null}
     */
    private function secretState(?string $saved, string $environment): array
    {
        if (filled($saved)) {
            return ['source' => 'admin', 'ends_with' => substr((string) $saved, -4)];
        }

        if ($environment !== '') {
            return ['source' => 'env', 'ends_with' => substr($environment, -4)];
        }

        return ['source' => null, 'ends_with' => null];
    }
}
