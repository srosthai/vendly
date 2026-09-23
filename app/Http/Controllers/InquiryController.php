<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\SendInquiry;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function product(Request $request, Store $store, Product $product, SendInquiry $action): RedirectResponse
    {
        abort_unless($product->store_id === $store->id, 404);
        abort_if($store->isSuspended(), 404);

        $action->forProduct($store, $product, $this->customer($request));

        return back()->with('status', 'Sent to the store on Telegram.');
    }

    public function cart(Request $request, Store $store, SendInquiry $action): RedirectResponse
    {
        abort_if($store->isSuspended(), 404);

        $action->forCart($store, $this->customer($request));

        return back()->with('status', 'Sent to the store on Telegram.');
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
