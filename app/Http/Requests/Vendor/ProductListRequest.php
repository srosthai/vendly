<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Admin\Lists\ListRequest;

/**
 * The vendor's products list. The store comes from the signed-in vendor, so
 * a category or brand id that is not theirs simply matches nothing.
 */
class ProductListRequest extends ListRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array{search: string, status: string, category: string, brand: string, sort: string}
     */
    public function filters(): array
    {
        $category = $this->query('category');
        $brand = $this->query('brand');

        return [
            'search' => $this->search(),
            'status' => $this->choice('status', ['all', 'published', 'draft', 'sold_out']),
            'category' => is_string($category) && ctype_digit($category) ? $category : 'all',
            'brand' => is_string($brand) && ctype_digit($brand) ? $brand : 'all',
            'sort' => $this->choice('sort', ['newest', 'name', 'price_low', 'price_high', 'stock'], 'newest'),
        ];
    }
}
