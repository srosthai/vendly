<?php

namespace App\Actions\Dashboard;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyCounts
{
    /**
     * Bucket timestamps (and optional amounts) into the last $days days,
     * oldest first, so a trend line always has one point per day.
     *
     * @param  Collection<int, array{at: CarbonInterface, amount?: int}>  $rows
     * @return list<int>
     */
    public function handle(Collection $rows, int $days = 7): array
    {
        $buckets = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $buckets[now()->subDays($offset)->toDateString()] = 0;
        }

        foreach ($rows as $row) {
            $day = $row['at']->toDateString();

            if (array_key_exists($day, $buckets)) {
                $buckets[$day] += $row['amount'] ?? 1;
            }
        }

        return array_values($buckets);
    }
}
