<?php

namespace App\System\Admin;

use App\System\Models\SystemUser;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/system/profile', name: 'system.profile')]
class Profile
{
    #[Route(methods: 'GET', route: '', name: 'info')]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');

        $info = SystemUser::query()->where('id', $auth['id'])->first();

        $data = format_data($info, function ($item) {
            return [
                'id' => $item->id,
                'nickname' => $item->nickname,
                'avatar' => $item->avatar,
                'tel' => $item->tel,
                'email' => $item->email,
                'lang' => $item->lang,
            ];
        });
        return send($response, "ok", ...$data);
    }

    #[Route(methods: 'PUT', route: '', name: 'update')]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getParsedBody();

        $rules = [
            'nickname' => ['required', '请传递昵称'],
        ];
        $data = Validator::parser($params, $rules);


        $form = [
            'nickname' => $data->nickname,
            'avatar' => $data->avatar,
            'tel' => $data->tel,
            'email' => $data->email,
            'lang' => $data->lang,
        ];

        if ($data->password) {
            $form['password'] = password_hash($data->password, PASSWORD_DEFAULT);
        }

        SystemUser::query()->where('id', $auth['id'])->update($form);

        return send($response, "ok");
    }
}
