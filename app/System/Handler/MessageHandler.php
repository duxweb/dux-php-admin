<?php

declare(strict_types=1);

namespace App\System\Handler;

use App\System\Models\SystemMessage;
use App\System\Models\SystemUser;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MessageHandler extends Resources
{

    protected string $hasModel = SystemUser::class;

    protected string $model = SystemMessage::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getQueryParams();
        $query->with(['user', 'sendUser', 'senderDept'])->where('user_has', $this->hasModel)->where('user_id', $auth['id']);

        switch ($params['tab']) {
            case 'unread':
                $query->where('read', false);
                break;
            case 'read':
                $query->where('read', true);
                break;
        }
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "sender" => $item->sender,
            "sender_dept" => $item->sender_dept,
            "icon" => $item->icon,
            "title" => $item->title,
            "desc" => $item->desc,
            "content" => $item->content,
            "read" => $item->read,
            "read_at" => $item->read_at,
        ];
    }

    public function metaMany(object|array $query, array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            'total' => $query->count(),
            'unread' => $query->where('read', false)->count(),
            'read' => $query->where('read', true)->count(),
        ];
    }

    #[Action(methods: 'PATCH', route: '/{id}')]
    public function store(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        SystemMessage::query()->where('id', $args['id'])->where('user_has', $this->hasModel)->where('user_id', $auth['id'])->update(['read' => true]);
        return send($response, "ok");
    }

    #[Action(methods: 'DELETE', route: '/{id}')]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        SystemMessage::query()->where('id', $args['id'])->where('user_has', $this->hasModel)->where('user_id', $auth['id'])->delete();
        return send($response, "ok");
    }

    #[Action(methods: 'PATCH', route: '/batch')]
    public function batch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getParsedBody();

        $method = $params['method'];
        $data = $params['data'] ?: [];

        switch ($method) {
            case 'read':
                if (!$data) {
                    SystemMessage::query()->where('user_has', $this->hasModel)->where('user_id', $auth['id'])->update(['read' => true]);
                } else {
                    SystemMessage::query()->where('user_has', $this->hasModel)->where('user_id', $auth['id'])->whereIn('id', $data)->update(['read' => true]);
                }
                break;
            case 'delete':
                if (!$data) {
                    SystemMessage::query()->where('user_has', $this->hasModel)->where('user_id', $auth['id'])->where('read', 1)->delete();
                } else {
                    SystemMessage::query()->where('user_has', $this->hasModel)->where('user_id', $auth['id'])->where('read', 1)->whereIn('id', $data)->delete();
                }
                break;
        }

        return send($response, "ok");
    }
}
