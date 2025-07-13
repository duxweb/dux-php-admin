<?php

declare(strict_types=1);

namespace App\Member\Admin;

use App\Member\Models\MemberBlockNickname;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/member/blockNickname', name: 'member.blockNickname')]
class BlockNickname extends Resources
{
    protected string $model = MemberBlockNickname::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();
        $search = $params["keyword"];
        if ($search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where("nickname", "like", "%$search%");
            });
        }
    }

    public function transform(object $item): array
    {
        return [
            'id' => $item->id,
            'nickname' => $item->nickname,
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "nickname" => ["required", '昵称不能为空'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            'nickname' => $data->nickname,
        ];
    }
}
