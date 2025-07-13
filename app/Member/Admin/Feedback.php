<?php

declare(strict_types=1);

namespace App\Member\Admin;

use App\Member\Models\MemberFeedback;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/member/feedback', name: 'member.feedback', actions: ['list', 'store', 'delete'])]
class Feedback extends Resources
{
    protected string $model = MemberFeedback::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args)
    {
        $query->with(['user'])->orderByDesc('id');
    }

    public function transform(object $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'user' => [
                'avatar' => $item->user?->avatar,
                'nickname' => $item->user?->nickname,
                'tel' => $item->user?->tel,
            ],
            'images' => $item->images,
            'content' => $item->content,
            'status' => (bool)$item->status,
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "status" => $data->status
        ];
    }

}
