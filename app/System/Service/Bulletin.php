<?php

declare(strict_types=1);

namespace App\System\Service;

use App\System\Models\SystemBulletin;
use App\System\Models\SystemBulletinRead;
use App\System\Models\SystemUser;

class Bulletin
{
    /**
     * 获取用户可见的公告列表
     */
    public static function getList(?string $userModel = null, ?int $userId = null, array $params = []): array
    {
        $query = static::buildQuery($userModel, $userId, $params);
        
        // 添加关联和已读筛选
        $query->with([
            'readRecords' => function($q) use ($userModel, $userId) {
                if ($userModel && $userId) {
                    $q->where('user_has', $userModel)
                      ->where('user_id', $userId);
                }
            },
            'readUsers'
        ]);

        // 已读筛选
        switch ($params['read'] ?? '') {
            case '1': 
                if ($userModel && $userId) {
                    $query->whereHas('readRecords', function($q) use ($userModel, $userId) {
                        $q->where('user_has', $userModel)
                          ->where('user_id', $userId);
                    });
                }
                break;
            case '2':
                if ($userModel && $userId) {
                    $query->whereDoesntHave('readRecords', function($q) use ($userModel, $userId) {
                        $q->where('user_has', $userModel)
                          ->where('user_id', $userId);
                    });
                }
                break;
        }

        $query->orderBy('is_top', 'desc')
              ->orderBy('sort', 'desc')
              ->orderBy('publish_at', 'desc');

        $list = $query->paginate();
        return format_data($list, function ($item) use ($userId) {
            return $item->transform($userId);
        });
    }

    /**
     * 获取已读未读统计
     */
    public static function getStats(?string $userModel = null, ?int $userId = null, array $params = []): array
    {
        if (!$userModel || !$userId) {
            return ['read_count' => 0, 'unread_count' => 0, 'total_count' => 0];
        }

        $baseQuery = static::buildQuery($userModel, $userId, $params);
        
        // 克隆查询用于统计
        $totalQuery = clone $baseQuery;
        $readQuery = clone $baseQuery;
        $unreadQuery = clone $baseQuery;

        $totalCount = $totalQuery->count();
        
        $readCount = $readQuery->whereHas('readRecords', function($q) use ($userModel, $userId) {
            $q->where('user_has', $userModel)
              ->where('user_id', $userId);
        })->count();

        $unreadCount = $unreadQuery->whereDoesntHave('readRecords', function($q) use ($userModel, $userId) {
            $q->where('user_has', $userModel)
              ->where('user_id', $userId);
        })->count();

        return [
            'total' => $totalCount,
            'read' => $readCount,
            'unread' => $unreadCount,
        ];
    }

    /**
     * 构建查询条件
     */
    private static function buildQuery(?string $userModel = null, ?int $userId = null, array $params = [])
    {
        $user = null;
        if ($userModel && $userId) {
            $user = $userModel::query()->with(['role', 'dept'])->find($userId);
        }

        $query = SystemBulletin::query()
            ->where('status', 1);

        // 权限过滤
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('target_type', 1)
                  ->orWhere(function ($subQ) use ($user) {
                      $subQ->where('target_type', 2)
                           ->whereJsonContains('target_departments', $user->dept_id);
                  })
                  ->orWhere(function ($subQ) use ($user) {
                      $subQ->where('target_type', 3)
                           ->whereJsonContains('target_roles', $user->role_id);
                  });
            });
        } else {
            // 没有用户信息时，只显示全部用户的公告
            $query->where('target_type', 1);
        }

        // 时间过滤
        $query->where(function ($q) {
            $q->whereNull('expire_at')
              ->orWhere('expire_at', '>', now());
        })
        ->where('publish_at', '<=', now());

        // 类型筛选
        switch ($params['type'] ?? '') {
            case '1': $query->where('type', 1); break;
            case '2': $query->where('type', 2); break;
            case '3': $query->where('type', 3); break;
        }

        return $query;
    }

    /**
     * 标记已读
     */
    public static function markRead(string $userModel, int $userId, int $bulletinId): void
    {
        SystemBulletinRead::query()->updateOrCreate([
            'bulletin_id' => $bulletinId,
            'user_has' => $userModel,
            'user_id' => $userId,
        ]);
    }
}