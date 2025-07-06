<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\LogLogin;
use App\System\Models\SystemMessage;
use App\System\Models\SystemUser;
use Carbon\Carbon;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/login', name: 'system.login', actions: ['list'])]
class Login extends Resources
{
    protected string $model = LogLogin::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();
        $auth = $request->getAttribute('auth');
        $query->with(['user'])->where('user_type', SystemUser::class)->orderBy('id', 'desc');
        if ($params['user_id']) {
            $query->where('user_id', $params['user_id']);
        } else {
            $query->where('user_id', $auth['id']);
        }
        switch ($params['tab']) {
            case '1':
                $query->where('status', true);
                break;
            case '2':
                $query->where('status', false);
                break;
        }
        if ($params['date']) {
            $query->whereBetween('created_at', [
                Carbon::createFromTimestampMs($params['date'][0])->startOfDay(),
                Carbon::createFromTimestampMs($params['date'][1])->endOfDay(),
            ]);
        }
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "username" => $item->user->username,
            "nickname" => $item->user->nickname,
            "avatar" => $item->user->avatar,
            "browser" => $item->browser,
            "ip" => $item->ip,
            "platform" => $item->platform,
            "status" => $item->status,
            "time" => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
