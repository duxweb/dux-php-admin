<?php

namespace App\System\Service;

use App\System\Models\SystemDept;
use App\System\Models\SystemUser;

class SystemNotice
{

    /**
     * 批量发送通知
     */
    public static function sendBatch(
        string $targetType,
        array $targetIds,
        string $title,
        ?string $content = null,
        array $options = []
    ): int {
        $userIds = self::getUserIdsByTarget($targetType, $targetIds);
        $count = 0;

        foreach ($userIds as $userId) {
            Notice::sendToUser(SystemUser::class, $userId, $title, $content, $options);
            $count++;
        }

        return $count;
    }

    /**
     * 发送通知给所有管理员
     */
    public static function sendToAll(
        string $title,
        ?string $content = null,
        array $options = []
    ): int {
        return self::sendBatch('all', [], $title, $content, $options);
    }

    /**
     * 发送通知给指定角色
     */
    public static function sendToRoles(
        array $roleIds,
        string $title,
        ?string $content = null,
        array $options = []
    ): int {
        return self::sendBatch('role', $roleIds, $title, $content, $options);
    }

    /**
     * 发送通知给指定部门
     */
    public static function sendToDepartments(
        array $deptIds,
        string $title,
        ?string $content = null,
        array $options = []
    ): int {
        return self::sendBatch('dept', $deptIds, $title, $content, $options);
    }

    /**
     * 发送通知给指定用户列表
     */
    public static function sendToUsers(
        array $userIds,
        string $title,
        ?string $content = null,
        array $options = []
    ): int {
        return self::sendBatch('user', $userIds, $title, $content, $options);
    }

    /**
     * 根据目标类型获取用户ID列表
     */
    private static function getUserIdsByTarget(string $targetType, array $targetIds): array
    {
        return match($targetType) {
            'all' => self::getAllUserIds(),
            'role' => self::getUserIdsByRoles($targetIds),
            'dept' => self::getUserIdsByDepartments($targetIds),
            'user' => $targetIds,
            default => []
        };
    }

    /**
     * 获取所有管理员用户ID
     */
    private static function getAllUserIds(): array
    {
        return SystemUser::query()
            ->where('status', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * 根据角色ID获取用户ID列表
     */
    private static function getUserIdsByRoles(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        return SystemUser::query()
            ->whereIn('role_id', $roleIds)
            ->where('status', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * 根据部门ID获取用户ID列表（包含子部门）
     */
    private static function getUserIdsByDepartments(array $deptIds): array
    {
        if (empty($deptIds)) {
            return [];
        }

        // 获取部门及其所有子部门
        $allDeptIds = [];
        foreach ($deptIds as $deptId) {
            $dept = SystemDept::query()->find($deptId);
            if ($dept) {
                $allDeptIds[] = $dept->id;
                // 获取所有子部门ID
                $descendants = $dept->descendants()->pluck('id')->toArray();
                $allDeptIds = array_merge($allDeptIds, $descendants);
            }
        }

        $allDeptIds = array_unique($allDeptIds);

        return SystemUser::query()
            ->whereIn('dept_id', $allDeptIds)
            ->where('status', true)
            ->pluck('id')
            ->toArray();
    }
}