<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property int $sort
 */
#[Fillable(['product_id', 'path', 'sort'])]
class ProductImage extends Model
{
    /**
     * The file leaves storage once its row is gone.
     */
    protected static function booted(): void
    {
        static::deleted(function (ProductImage $image): void {
            DB::afterCommit(fn () => Storage::disk('public')->delete($image->path));
        });
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
