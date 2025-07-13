<?php

namespace App\Member\Service;

use App\Member\Models\MemberUser;
use App\System\Models\LogVisitData;
use Carbon\Carbon;
use Core\App;

class Stats
{

    // 订单销售数据
    public static function views(Carbon $startDate, Carbon $endDate): array
    {
        $result = LogVisitData::query()
            ->select(
                App::db()->getConnection()->raw('SUM(`pv`) as pv'),
                App::db()->getConnection()->raw('SUM(`uv`) as uv'),
            )
            ->whereBetween('date',  [$startDate, $endDate])
            ->where('has_type', 'common')
            ->first();

        return [
            $result->pv ?: 0,
            $result->uv ?: 0,
        ];
    }

    public static function newUser(Carbon $startTime, Carbon $endTime): int
    {
        return MemberUser::query()->whereBetween('created_at', [$startTime, $endTime])->count();
    }

}