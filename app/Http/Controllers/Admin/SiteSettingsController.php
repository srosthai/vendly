<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePaymentMethodRequest;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Models\PaymentMethod;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What the public website footer shows: contact details, social links, and
 * the payment methods listed under "We accept".
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
            $method->logo_path = $request->file('logo')->store('payment-methods', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $method->logo_path = null;
        }

        $method->save();

        if ($oldLogo !== null && $oldLogo !== $method->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }
    }
}
