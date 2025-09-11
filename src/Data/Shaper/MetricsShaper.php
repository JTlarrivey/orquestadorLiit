<?php

namespace App\Data\Shaper;

use App\Data\DataShaperInterface;

class MetricsShaper implements DataShaperInterface
{
    public function shape(array $rawData): array
    {
        return [
            'labels'   => array_column($rawData, 'month'),
            'ventas'   => array_column($rawData, 'ventas'),
            'usuarios' => array_column($rawData, 'usuarios'),
        ];
    }
}
