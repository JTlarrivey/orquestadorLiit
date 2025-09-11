<?php

namespace App\Data;

interface DataShaperInterface
{
    public function shape(array $rawData): array;
}
