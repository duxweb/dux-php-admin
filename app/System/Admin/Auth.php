<?php

namespace App\System\Admin;

use App\System\Models\LogLogin;
use App\System\Models\SystemUser;
use donatj\UserAgent\UserAgentParser;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/', name: 'auth', actions: false)]
class Auth
{
    #[Action(methods: 'POST', route: 'login', auth: false)]
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser([...$request->getParsedBody(), ...$args], [
            "username" => ["required", '用户名不能为空'],
            "password" => ["required", '密码不能为空'],
        ]);
        $info = SystemUser::query()->where("username", $data->username)->first();
        if (!$info) {
            throw new ExceptionBusiness('用户不存在');
        }

        $this->loginCheck((int)$info->id);

        $useragent = $request->getHeader("user-agent")[0];
        $parser = new UserAgentParser();
        $ua = $parser->parse($useragent);
        $loginModel = LogLogin::query();
        $logData = [
            'user_type' => SystemUser::class,
            'user_id' => $info->id,
            'browser' => $ua->browser() . ' ' . $ua->browserVersion(),
            'ip' => get_ip(),
            'platform' => $ua->platform(),
        ];
        if (!password_verify($data->password, $info->password)) {
            $logData['status'] = false;
            $loginModel->create($logData);
            throw new ExceptionBusiness('密码错误');
        }

        $logData['status'] = true;
        $loginModel->create($logData);

        return send($response, "ok", [
            "info" => [
                "id" => $info->id,
                "avatar" => $info->avatar,
                "username" => $info->username,
                "nickname" => $info->nickname,
                "rolename" => $info->roles[0]->name,
            ],
            "token" => \Core\Auth\Auth::token("admin", [
                'id' => $info->id,
            ]),
            "permission" => $info->permission
        ]);
    }

    #[Action(methods: 'GET', route: 'check')]
    public function check(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');

        $info = SystemUser::query()->where('status', 1)->find($auth['id']);
        if (!$info) {
            throw new ExceptionBusiness('登录已过期', 401);
        }

        return send($response, "ok", [
            "info" => [
                "id" => $info->id,
                "avatar" => $info->avatar,
                "username" => $info->username,
                "nickname" => $info->nickname,
                "rolename" => $info->roles[0]->name,
            ],
            "token" => \Core\Auth\Auth::token("admin", [
                'id' => $info->id,
            ]),
            "permission" => $info->permission
        ]);

    }

    private function loginCheck(int $id): void
    {
        $lasSeconds = now()->subSeconds(60);
        $loginList = LogLogin::query()->where('user_type', SystemUser::class)->where([
            'user_id' => $id,
            'status' => false,
            ['created_at', '>=', $lasSeconds->toDateTimeString()]
        ])->orderByDesc('id')->limit(3)->get();
        $loginLast = $loginList->first();
        $loginStatus = $loginList->count();
        $time = now();
        if ($loginStatus >= 3 && $loginLast->created_at->addSeconds(60)->gt($time)) {
            throw new ExceptionBusiness('密码错误次数过多，请稍后再试');
        }
    }
}
