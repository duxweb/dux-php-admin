<?php

namespace App\Member\Service;

use App\Member\Interface\CommentInterface;
use App\Member\Models\MemberComment;
use App\Member\Models\MemberNoticeComment;
use App\Member\Models\MemberUser;
use App\System\Service\Config;
use Core\Handlers\ExceptionBusiness;

class Comment
{

    /**
     * 发布评论
     * @param int $userId
     * @param string $hasType
     * @param int $hasId
     * @param string|null $content
     * @param int|null $replyId
     * @return void
     */
    public static function push(int $userId, string $hasType, int $hasId, ?array $content = [], ?int $replyId = null, ?array $address = [], ?CommentInterface $comment = null): void
    {
        $lastInfo = MemberComment::query()->where('user_id', $userId)->where('has_type', $hasType)->where('has_id', $hasId)->orderByDesc('id')->first();
        if ($lastInfo) {
            $config = Config::getJsonValue('member');
            if (now()->subSeconds($config['comment_interval'] ?? 60)->lt($lastInfo->created_at)) {
                throw new ExceptionBusiness('发送太频繁，请稍后再发送');
            }
            if ($lastInfo->content == $content) {
                throw new ExceptionBusiness('请勿发布重复评论');
            }
        }

        $data = [
            'user_id' => $userId,
            'has_type' => $hasType,
            'has_id' => $hasId,
            'content' => $content['content'] ?: '',
            'image' => $content['image'] ?: '',
            'ip' => $address['ip'] ?: '',
            'country' => $address['country'] ?: '',
            'province' => $address['province'] ?: '',
            'city' => $address['city'] ?: '',
            'status' => 1
        ];

        if ($replyId) {
            $replyInfo = MemberComment::query()->where('id', $replyId)->where('has_type', $hasType)->where('has_id', $hasId)->where('status', 1)->exists();
            if (!$replyInfo) {
                throw new ExceptionBusiness('回复的评论不存在');
            }
            $data['parent_id'] = $replyId;
        }

        $info = MemberComment::query()->create($data);

        // 关联用户通知
        if ($info->hastable?->user_id) {
            MemberNoticeComment::query()->create([
                'comment_id' => $info->id,
                'user_id' => $info->hastable->user_id,
                'from_user_id' => $userId,
                'has_type' => $hasType,
                'has_id' => $hasId,
                'type' => 'comment',
                'content' => $content,
                'cover' => $info->hastable?->cover
            ]);
        }

        // @at
        preg_match_all('/@([^\s#]+)/', $content, $matches);
        if (!empty($matches[1])) {
            $atUsers = array_unique($matches[1]);
            // 验证用户是否存在
            $existUsers = MemberUser::query()
                ->whereIn('nickname', $atUsers)
                ->pluck('id')
                ->toArray();

            // 创建@通知
            foreach ($existUsers as $uid) {
                MemberNoticeComment::create([
                    'comment_id' => $info->id,
                    'user_id' => $uid,
                    'from_user_id' => $userId,
                    'has_type' => $hasType,
                    'has_id' => $hasId,
                    'type' => 'at',
                    'content' => $content,
                    'cover' => $info->cover
                ]);
            }
        }

        if ($comment) {
            $comment->callback($hasId, $info);
        }
    }

    public static function count(int $userId, string $hasType, ?int $hasId = 0): int
    {
        $query = MemberComment::query()->where('user_id', $userId)->where('has_type', $hasType);
        if ($hasId) {
            $query->where('has_id', $hasId);
        }
        return $query->count();
    }


}