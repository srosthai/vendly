<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inquiry_id
 * @property int|null $product_id
 * @property string $name
 * @property int $price_cents
 * @property int $quantity
 */
#[Fillable(['inquiry_id', 'product_id', 'name', 'price_cents', 'quantity'])]
class InquiryItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Inquiry, $this>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
