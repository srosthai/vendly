<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $role
 * @property string $quote
 * @property CarbonInterface|null $published_at
 * @property int $sort
 */
#[Fillable(['name', 'role', 'quote', 'published_at', 'sort'])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    /**
     * @param  Builder<Testimonial>  $query
     * @return Builder<Testimonial>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->orderBy('sort')->orderByDesc('published_at')->orderBy('id');
    }
}
