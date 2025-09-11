<?php

namespace App\Data\Shaper;

use App\Data\DataShaperInterface;

class UserProfileShaper implements DataShaperInterface
{
    public function shape(array $rawData): array
    {
        return [
            'userId' => $rawData['user_id'],
            'name' => $rawData['name'],
            'email' => $rawData['email']
        ];
    }
}
