<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slug
{
    /**
     * A link-safe slug that is free within the given query. A name with no
     * Latin letters, such as Khmer, gets a short random slug instead of an
     * error, and a taken slug gets -2, -3, and so on.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $scope
     */
    public static function unique(string $name, Builder $scope, string $fallbackPrefix, int $maxLength = 80): string
    {
        $base = Str::limit(Str::slug($name), $maxLength, '');
        $base = trim($base, '-');

        if ($base === '') {
            $base = $fallbackPrefix.'-'.Str::lower(Str::random(6));
        }

        $slug = $base;
        $suffix = 2;

        while ((clone $scope)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
