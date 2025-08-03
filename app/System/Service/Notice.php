<?php

namespace App\System\Service;

use App\System\Models\SystemNotice;

class Notice
{
    /**
     * 发送通知给指定用户
     */
    public static function sendToUser(
        string $userModel,
        int $userId,
        string $title,
        ?string $content = null,
        array $options = []
    ): SystemNotice {
        $data = array_merge([
            'user_has' => $userModel,
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
        ], $options);
        return SystemNotice::query()->create($data);
    }

    /**
     * 批量标记为已读
     */
    public static function markRead(string $userHas, int $userId, ?array $ids = null)
    {
        $query = SystemNotice::query()
            ->where('user_has', $userHas)
            ->where('user_id', $userId)
            ->where('is_read', false);
            
        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $query->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * 获取用户未读通知数量
     */
    public static function getUnreadCount(string $userHas, int $userId): int
    {
        return SystemNotice::query()
            ->where('user_has', $userHas)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * 获取用户通知统计
     */
    public static function getStats(string $userHas, int $userId): array
    {
        $total = SystemNotice::query()
            ->where('user_has', $userHas)
            ->where('user_id', $userId)
            ->count();

        $unread = SystemNotice::query()
            ->where('user_has', $userHas)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $total - $unread,
        ];
    }

    /**
     * 获取通知列表
     */
    public static function getList(array $params = [], ?string $userModel = null, ?int $userId = null): array
    {
        $query = SystemNotice::query();

        if ($userModel && $userId) {
            $query->where('user_has', $userModel)
                  ->where('user_id', $userId);
        }

        // Tab筛选
        switch ($params['tab'] ?? '') {
            case '1': $query->where('is_read', false); break; // 未读
            case '2': $query->where('is_read', true); break;  // 已读
        }

        // 关键词搜索
        if (!empty($params['keyword'])) {
            $query->where(function ($q) use ($params) {
                $q->where('title', 'like', '%' . $params['keyword'] . '%')
                  ->orWhere('content', 'like', '%' . $params['keyword'] . '%');
            });
        }

        // 排序：未读在前，按创建时间倒序
        $query->orderBy('is_read', 'asc')
              ->orderBy('created_at', 'desc');

        $list = $query->paginate(20);
        
        return format_data($list, function ($item) {
            return $item->transform();
        });
    }

    /**
     * 获取通知详情
     */
    public static function getDetail(int $noticeId, ?string $userModel = null, ?int $userId = null): ?array
    {
        $query = SystemNotice::query()->where('id', $noticeId);

        if ($userModel && $userId) {
            $query->where('user_has', $userModel)
                  ->where('user_id', $userId);
        }

        $notice = $query->first();
        
        return $notice ? $notice->transform() : null;
    }

    /**
     * 删除通知
     */
    public static function delete(string $userHas, int $userId, ?array $ids = null)
    {
        $query = SystemNotice::query()
            ->where('user_has', $userHas)
            ->where('user_id', $userId);
            
        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $query->delete();
    }
}