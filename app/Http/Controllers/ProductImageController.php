<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductImageController extends Controller
{
    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        $image->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Photo removed.']);

        return back();
    }

    /**
     * The cover is the first photo customers see on the card and the page.
     */
    public function cover(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        $image->update(['sort' => (int) $product->images()->min('sort') - 1]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cover photo set.']);

        return back();
    }
}
