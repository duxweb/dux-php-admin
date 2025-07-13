<?php

declare(strict_types=1);

namespace App\Member\Admin;

use App\Member\Models\MemberNotice;
use Core\App;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Validator;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/member/notice', name: 'member.notice', actions: ['list', 'delete'])]
class Notice extends Resources
{
    protected string $model = MemberNotice::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args)
    {
        $query->where('type', 1)->orderByDesc('id');

        $params = $request->getQueryParams();

        if ($params['keyword']) {
            $query->where('title', 'like', '%' . $params['keyword'] . '%');
        }
    }

    public function transform(object $item): array
    {
        return [
            'id' => $item->id,
            'image' => $item->image,
            'title' => $item->title,
            'desc' => $item->desc,
            'url' => $item->url,
            'created_at' => $item->created_at->format('Y-m-d H:i:s'),
        ];
    }

    #[Action(methods: 'POST', route: '/push')]
    public function push(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody() ?: [];
        $id = (int) $args['id'];

        $data = Validator::parser($params, [
            "title" => ["required", "请输入公告标题"],
            "desc" => ["required", "请输入公告描述"],
        ]);


        try {
            App::db()->getConnection()->beginTransaction();
            \App\Member\Service\Notice::send('system', [], (string)$data->title, (string)$data->desc, (string)$data->url, (string)$data->image);
            App::db()->getConnection()->commit();
        } catch (\Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }

        return send($response, '发布成功');
    }


}
