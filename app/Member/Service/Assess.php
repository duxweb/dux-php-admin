<?php

namespace App\Member\Service;

use App\Member\Interface\AssessInterface;
use App\Member\Models\MemberAssess;
use Core\Handlers\ExceptionBusiness;

class Assess
{

    /**
     * 发布评价
     * @param int $userId
     * @param string $hasType 关联类
     * @param int $hasId 关联 id
     * @param array $comment 评价信息
     * @param array $content 内容信息
     * @param string|null $sourceType 来源类
     * @param int|null $sourceId 来源 id
     * @return void
     */
    public static function push(int $userId, string $hasType, int $hasId, array $comment = [], ?string $sourceType = '', ?int $sourceId = null, ?AssessInterface $assess = null): void
    {
        $lastInfo = MemberAssess::query()->where('user_id', $userId)->where('source_type', $sourceType)->where('source_id', $sourceId)->orderByDesc('id')->first();
        if ($lastInfo) {
            throw new ExceptionBusiness('请勿重复评价');
        }


        $content = $assess->content($hasId);

        $data = [
            'user_id' => $userId,
            'has_type' => $hasType,
            'has_id' => $hasId,

            'title' => $content['title'] ?: '',
            'desc' => $content['desc'] ?: '',
            'image' => $content['image'] ?: '',

            'content' => $comment['content'] ?: '暂无评价',
            'images' => $comment['images'] ?: [],
            'score' => $comment['score'] ?: 5,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'status' => 1
        ];

        $info = MemberAssess::query()->create($data);

        // 更新评分和计数
        $count = MemberAssess::query()->where('user_id', $userId)->where('has_type', $hasType)->where('has_id', $hasId)->count();
        $sumScore = MemberAssess::query()->where('user_id', $userId)->where('has_type', $hasType)->where('has_id', $hasId)->sum('score');
        $score = $sumScore / $count;

        if ($assess) {
            $assess->callback($hasId, $score, $info);
        }

    }

    /**
     * 获取评价评分
     * @param string $hasType
     * @param int|null $hasId
     * @return string
     */
    public static function calculateScore(string $hasType, ?int $hasId = null): string
    {
        $query = MemberAssess::query()->where('has_type', $hasType);
        if ($hasId) {
            $query->where('has_id', $hasId);
        }
        $sum = $query->sum('score');
        $count = $query->count();
        return $count ? bc_math($sum, '/', $count) : '5';
    }

    /**
     * 格式化数据
     * @param $item
     * @return array
     */
    public static function formatData($item, ?AssessInterface $assess = null): array
    {

        $base = [
            "id" => $item->id,
            "user_id" => $item->user_id,
            "user" => [
                "nickname" => $item->user->nickname,
                "avatar" => $item->user->avatar,
                "tel" => $item->user->tel,
                "email" => $item->user->email,
            ],
            "title" => $item->title,
            "desc" => $item->desc,
            "image" => $item->image,
            'content' => $item->content,
            'images' => $item->images,
            'score' => $item->score,
            'status' => (bool)$item->status,
        ];

        if ($assess) {
            $base = [...$base, ...$assess->format($item)];
        }

        return $base;
    }

    /**
     * 统计数量
     * @param int $userId
     * @param string $hasType
     * @param int|null $hasId
     * @return int
     */
    public static function count(int $userId, string $hasType, ?int $hasId = 0): int
    {
        $query = MemberAssess::query()->where('user_id', $userId)->where('has_type', $hasType);
        if ($hasId) {
            $query->where('has_id', $hasId);
        }
        return $query->count();
    }


}