<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price_cents
 * @property int|null $stock
 * @property ProductStatus $status
 */
#[Fillable(['store_id', 'category_id', 'brand_id', 'name', 'slug', 'description', 'price_cents', 'stock', 'status'])]
class Product extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'stock' => 'integer',
            'status' => ProductStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    public function isSoldOut(): bool
    {
        return $this->stock !== null && $this->stock < 1;
    }
}
