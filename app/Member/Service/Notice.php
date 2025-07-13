<?php

namespace App\Member\Service;

use App\Member\Models\MemberNotice;
use App\Member\Models\MemberNoticeRead;
use App\Member\Models\MemberUser;
use DI\DependencyException;
use DI\NotFoundException;
use Core\App;

class Notice
{

    /**
     * 发送通知
     * @param array $userIds
     * @param string $title
     * @param string $desc
     * @param string $url
     * @param string $image
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function send(string $classType, array $userIds, string $title, string $desc, string $url = '', string $image = '', array $extra = [], array $translations = []): void
    {
        $type = $userIds ? 0 : 1;

        $data = [];
        if ($type) {
            // 全部
            $data[] = [
                'class_type' => $classType,
                'type' => $type,
                'user_id' => null,
                'title' => $title,
                'desc' => $desc,
                'image' => $image,
                'url' => $url,
                'data' => json_encode($extra),
                'translations' => json_encode($translations),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } else {
            // 用户
            foreach ($userIds as $userId) {
                $data[] = [
                    'class_type' => $classType,
                    'type' => $type,
                    'user_id' => $userId,
                    'title' => $title,
                    'desc' => $desc,
                    'image' => $image,
                    'url' => $url,
                    'data' => json_encode($extra),
                    'translations' => json_encode($translations),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        $list = array_chunk($data, 1000);
        foreach ($list as $vo) {
            App::db()->getConnection()->table('member_notice')->insert($vo);
        }
    }

    public static function list(int $userId, string $classType = ''): array
    {
        $userInfo = MemberUser::query()->find($userId);
        $notice = MemberNotice::query()
            ->with(['read' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('type', 1);
            })
            ->when($classType, function ($query) use ($classType) {
                $query->where('class_type', $classType);
            })
            ->where('created_at', '>=', $userInfo->created_at->format('Y-m-d H:i:s'))
            ->orderByDesc('id')
            ->paginate(15);

        $result = format_data($notice, function ($data) {
            return [
                'id' => $data->id,
                'class_type' => $data->class_type,
                'title' => $data->title,
                'desc' => $data->desc,
                'image' => $data->image,
                'url' => $data->url,
                'data' => $data->data,
                'created_at' => $data->created_at->format('Y-m-d H:i:s'),
                'read' => $data->read->isNotEmpty()
            ];
        });

        return $result;
    }

    public static function read(int $userId, array $ids = []): void
    {
        if (!$ids) {
            $ids = MemberNotice::query()->where('user_id', $userId)->orWhere('type', 1)->pluck('id')->toArray();
        }
        $noticeIds = MemberNoticeRead::query()->whereIn('notice_id', $ids)->where('user_id', $userId)->pluck('notice_id')->toArray();
        $result = array_diff($ids, $noticeIds);

        $data = [];
        foreach ($result as $id) {
            $data[] = [
                'user_id' => $userId,
                'notice_id' => $id,
            ];
        }
        MemberNoticeRead::query()->insert($data);
    }
}
