<?php

namespace App\Http\Requests\Admin\Lists;

class TestimonialListRequest extends ListRequest
{
    /**
     * @return array{search: string, status: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->search(),
            'status' => $this->choice('status', ['all', 'published', 'draft']),
        ];
    }
}
