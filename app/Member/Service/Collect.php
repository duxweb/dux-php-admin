<?php

namespace App\Member\Service;

use App\Member\Models\MemberCollect;
use App\Member\Models\MemberComment;
use App\Member\Interface\CollectInterface;

class Collect
{
    /**
     * 收藏和取消收藏
     * @param int $userId
     * @param string $hasType
     * @param int $hasId
     * @return void
     */
    public static function run(int $userId, string $hasType, int $hasId, ?CollectInterface $collect = null): void
    {
        $info = MemberCollect::query()->where('user_id', $userId)->where('has_type', $hasType)->where('has_id', $hasId)->first();
        if ($info) {
            $info->delete();
            if ($collect) {
                $collect->callback($hasId, false);
            }
            return;
        }
        $data = [
            'user_id' => $userId,
            'has_type' => $hasType,
            'has_id' => $hasId,
        ];
        $info = MemberCollect::query()->create($data);
        if ($collect) {
            $collect->callback($hasId, true);
        }
    }

    public static function count(int $userId, string $hasType, ?int $hasId = 0): int
    {
        $query = MemberCollect::query()->where('user_id', $userId)->where('has_type', $hasType);
        if ($hasId) {
            $query->where('has_id', $hasId);
        }
        return $query->count();
    }

}