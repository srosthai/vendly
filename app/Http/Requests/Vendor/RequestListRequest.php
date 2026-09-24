<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Admin\Lists\ListRequest;

/**
 * The vendor's requests list. The store comes from the signed-in vendor.
 */
class RequestListRequest extends ListRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array{search: string, status: string, kind: string, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->search(),
            'status' => $this->choice('status', ['all', 'new', 'handled']),
            'kind' => $this->choice('kind', ['all', 'buy', 'cart']),
            'sort' => $this->choice('sort', ['newest', 'oldest'], 'newest'),
        ];
    }
}
