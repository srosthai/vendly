<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Products always belong to a store: create them with `->for($store)`.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price_cents' => fake()->numberBetween(100, 5000),
            'stock' => null,
            'status' => ProductStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Draft]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (): array => ['stock' => 0]);
    }
}
