<?php

namespace App\Member\Service;

use App\Member\Interface\PraiseInterface;
use App\Member\Models\MemberPraise;

class Praise
{
    /**
     * 点赞和取消
     * @param int $userId
     * @param string $hasType
     * @param int $hasId
     * @return bool
     */
    public static function run(int $userId, string $hasType, int $hasId, ?PraiseInterface $praise = null): bool
    {
        $info = MemberPraise::query()->where('user_id', $userId)->where('has_type', $hasType)->where('has_id', $hasId)->first();
        if ($info) {
            $info->delete();
            if ($praise) {
                $praise->callback($hasId, false);
            }
            return false;
        }
        $data = [
            'user_id' => $userId,
            'has_type' => $hasType,
            'has_id' => $hasId,
        ];
        $info = MemberPraise::query()->create($data);
        if ($praise) {
            $praise->callback($hasId, true);
        }
        return true;

    }

}