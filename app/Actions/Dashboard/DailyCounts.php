<?php

namespace App\Actions\Dashboard;

use Carbon\CarbonInterface;

class DailyCounts
{
    /**
     * Bucket timestamps (and optional amounts) into the last $days days,
     * oldest first, so a trend line always has one point per day. Rows
     * without a time are skipped.
     *
     * @param  iterable<array{at: CarbonInterface|null, amount?: int}>  $rows
     * @return list<int>
     */
    public function handle(iterable $rows, int $days = 7): array
    {
        $buckets = array_fill_keys($this->days($days), 0);

        foreach ($rows as $row) {
            $day = $row['at']?->toDateString();

            if ($day !== null && array_key_exists($day, $buckets)) {
                $buckets[$day] += $row['amount'] ?? 1;
            }
        }

        return array_values($buckets);
    }

    /**
     * The dates of the last $days days, oldest first, as Y-m-d.
     *
     * @return list<string>
     */
    public function days(int $days = 7): array
    {
        $dates = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $dates[] = now()->subDays($offset)->toDateString();
        }

        return $dates;
    }
}
