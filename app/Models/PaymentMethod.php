<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A payment method the website lists under "We accept".
 *
 * @property int $id
 * @property string $name
 * @property string|null $logo_path
 * @property int $sort
 */
#[Fillable(['name', 'logo_path', 'sort'])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (PaymentMethod $method): void {
            if ($method->logo_path !== null) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($method->logo_path));
            }
        });
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path === null ? null : Storage::disk('public')->url($this->logo_path);
    }
}
