<?php

namespace App\Member\Service;

use App\Member\Models\MemberWindow;
use DI\DependencyException;
use DI\NotFoundException;
use Core\App;

class Window
{

    /**
     * 发送弹窗
     * @param array $userIds
     * @param string $type
     * @param string $title
     * @param string $desc
     * @param string $url
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function send(array $userIds, string $type, string $title, string $desc, ?array $extend = [], string $url = ''): void
    {
        // 用户
        foreach ($userIds as $userId) {
            $data[] = [
                'type' => $type,
                'user_id' => $userId,
                'title' => $title,
                'desc' => $desc,
                'url' => $url,
                'data' => json_encode($extend, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $list = array_chunk($data, 1000);
        foreach ($list as $vo) {
            App::db()->getConnection()->table('member_window')->insert($vo);
        }
    }

    public static function list(int $userId): array
    {
        return MemberWindow::query()
            ->where('user_id', $userId)
            ->where('status', 1)
            ->limit(10)
            ->get()->toArray();
    }

    public static function close(int $userId, array $ids = []): void
    {
        MemberWindow::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update([
                'status' => 0
            ]);
    }

}