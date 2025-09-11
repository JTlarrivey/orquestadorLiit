<?php

namespace App\Data\Factory;

use App\Data\DataShaperInterface;
use App\Data\Shaper\MetricsShaper;

class DataShaperFactory
{
    public static function getShaperFor(string $type): ?DataShaperInterface
    {
        return match ($type) {
            'metrics' => new MetricsShaper(),
            default   => null
        };
    }
}
