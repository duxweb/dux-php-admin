<?php

namespace App\Member\Service;

use App\Member\Interface\CollectInterface;
use App\Member\Models\MemberFoot;

class Foot
{
    /**
     * 足迹记录
     * @param int $userId
     * @param string $hasType
     * @param int $hasId
     * @return void
     */
    public static function run(int $userId, string $hasType, int $hasId,): void
    {
        $lastInfo = MemberFoot::query()->where('user_id', $userId)->where('has_type', $hasType)->where('has_id', $hasId)->whereBetween('created_at', [
            now()->startOfDay(),
            now()->endOfDay()
        ])->first();
        if ($lastInfo) {
            $lastInfo->touch();
            return;
        }
        $data = [
            'user_id' => $userId,
            'has_type' => $hasType,
            'has_id' => $hasId,
        ];
        MemberFoot::query()->create($data);
    }

}