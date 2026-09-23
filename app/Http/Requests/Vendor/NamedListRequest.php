<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Admin\Lists\ListRequest;

/**
 * The vendor's categories or brands list.
 */
class NamedListRequest extends ListRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array{search: string, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->search(),
            'sort' => $this->choice('sort', ['default', 'name', 'products', 'newest'], 'default'),
        ];
    }
}
