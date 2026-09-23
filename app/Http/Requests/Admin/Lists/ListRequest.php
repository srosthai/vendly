<?php

namespace App\Http\Requests\Admin\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The query string of an admin list: search, filters, and sort. Unknown
 * filter or sort values fall back to their defaults instead of failing, so
 * an old or edited link still opens the list.
 */
abstract class ListRequest extends FormRequest
{
    public const PerPage = 20;

    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:30'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * The normalized filters, sent back to the page so its toolbar shows
     * what is applied.
     *
     * @return array<string, string>
     */
    abstract public function filters(): array;

    public function search(): string
    {
        return trim($this->string('search')->toString());
    }

    /**
     * @param  list<string>  $allowed
     */
    protected function choice(string $key, array $allowed, string $default = 'all'): string
    {
        $value = $this->query($key);

        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * The "LIKE" pattern for the search. A % or _ someone types still acts
     * as a wildcard, which only widens an admin search, and keeps the
     * pattern the same on every database.
     */
    public function searchPattern(): string
    {
        return '%'.$this->search().'%';
    }
}
