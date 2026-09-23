<?php

namespace App\Http\Requests\Admin\Lists;

class PlanListRequest extends ListRequest
{
    /**
     * @return array{search: string, availability: string, price: string, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->search(),
            'availability' => $this->choice('availability', ['all', 'available', 'hidden']),
            'price' => $this->choice('price', ['all', 'free', 'paid']),
            'sort' => $this->choice('sort', ['newest', 'price', 'limit'], 'newest'),
        ];
    }
}
