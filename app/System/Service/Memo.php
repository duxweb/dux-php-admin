<?php

declare(strict_types=1);

namespace App\System\Service;

use App\System\Models\SystemMemo;
use Core\Handlers\ExceptionBusiness;

class Memo
{
    /**
     * 获取备忘录列表
     */
    public static function getList(string $userModel, int $userId, array $params = []): array
    {
        $query = SystemMemo::query()
            ->where('user_has', $userModel)
            ->where('user_id', $userId);

        // Tab筛选
        switch ($params['tab'] ?? '') {
            case '1': $query->where('is_completed', false); break;
            case '2': $query->where('is_completed', true); break;
        }

        // 优先级筛选
        if (!empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        // 关键字搜索
        if (!empty($params['keyword'])) {
            $query->where(function ($q) use ($params) {
                $q->where('title', 'like', '%' . $params['keyword'] . '%')
                  ->orWhere('content', 'like', '%' . $params['keyword'] . '%');
            });
        }

        $query->orderBy('is_completed', 'asc')
              ->orderBy('priority', 'desc')
              ->orderBy('remind_at', 'asc')
              ->orderBy('created_at', 'desc');

        $list = $query->paginate();
        return format_data($list, function ($item) {
            return $item->transform();
        });
    }

    /**
     * 获取备忘录详情
     */
    public static function getDetail(int $memoId, string $userModel, int $userId): ?array
    {
        $memo = SystemMemo::query()
            ->where('id', $memoId)
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->first();

        if (!$memo) {
            throw new ExceptionBusiness('备忘录不存在');
        }

        return $memo->transform();
    }

    /**
     * 创建备忘录
     */
    public static function create(string $userModel, int $userId, array $data): array
    {
        $memo = SystemMemo::query()->create([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'priority' => $data['priority'] ?? 1,
            'remind_at' => $data['remind_at'] ?? null,
            'user_has' => $userModel,
            'user_id' => $userId,
        ]);

        return $memo->transform();
    }

    /**
     * 更新备忘录
     */
    public static function update(int $memoId, string $userModel, int $userId, array $data): array
    {
        $memo = SystemMemo::query()
            ->where('id', $memoId)
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->first();

        if (!$memo) {
            throw new ExceptionBusiness('备忘录不存在');
        }

        $memo->update([
            'title' => $data['title'] ?? $memo->title,
            'content' => $data['content'] ?? $memo->content,
            'priority' => $data['priority'] ?? $memo->priority,
            'remind_at' => $data['remind_at'] ?? $memo->remind_at,
        ]);

        return $memo->fresh()->transform();
    }

    /**
     * 删除备忘录
     */
    public static function delete(string $userModel, int $userId, array $ids): void
    {
        SystemMemo::query()
            ->whereIn('id', $ids)
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * 标记完成状态
     */
    public static function toggleComplete(int $memoId, string $userModel, int $userId, bool $isCompleted = true): void
    {
        $memo = SystemMemo::query()
            ->where('id', $memoId)
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->first();

        if (!$memo) {
            throw new ExceptionBusiness('备忘录不存在');
        }

        $memo->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);
    }

    /**
     * 获取统计信息
     */
    public static function getStats(string $userModel, int $userId): array
    {
        $total = SystemMemo::query()
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->count();

        $completed = SystemMemo::query()
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $pending = $total - $completed;

        $expired = SystemMemo::query()
            ->where('user_has', $userModel)
            ->where('user_id', $userId)
            ->where('is_completed', false)
            ->where('remind_at', '<', now())
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'expired' => $expired,
        ];
    }
}