<?php

namespace App\Support;

class ActivityLogSearch
{
    public static function ftsMatchExpression(string $search): ?string
    {
        if (preg_match_all('/[\p{L}\p{N}_]+/u', $search, $matches) === false || $matches[0] === []) {
            return null;
        }

        return implode(' AND ', array_map(
            fn (string $term) => '"'.$term.'"*',
            $matches[0]
        ));
    }
}
