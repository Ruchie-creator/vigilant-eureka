<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SearchConsoleSyncCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $websiteId,
        public readonly int $syncId,
        public readonly string $property,
        public readonly string $dateStart,
        public readonly string $dateEnd,
        public readonly int $clicks,
        public readonly int $impressions,
        public readonly float $ctr,
        public readonly float $averagePosition,
        public readonly array $previousPeriodComparison = [],
    ) {}
}
