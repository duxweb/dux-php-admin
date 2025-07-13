<?php

namespace App\Member\Api;

use App\Member\Models\MemberUser;
use App\Member\Service\Member;
use App\Send\Service\Email;
use App\Send\Service\Sms;
use App\System\Service\Config;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Validator\Validator;
use Overtrue\EasySms\PhoneNumber;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Docs(name: '用户认证')]
class Auth
{
    private bool $debug = false;

    #[Route(methods: 'POST', route: '/member/login', app: 'api')]
    #[Api(name: '登录', payloadExample: ['username' => '13800138000', 'password' => 'password123', 'type' => 'password', 'code' => '123456'])]
    #[Payload(field: 'username', type: FieldEnum::STRING, name: '用户名', desc: '手机号或邮箱')]
    #[Payload(field: 'password', type: FieldEnum::STRING, name: '密码', required: false, desc: '用户密码')]
    #[Payload(field: 'type', type: FieldEnum::STRING, name: '登录方式', required: false, desc: 'password或code')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '验证码', required: false, desc: '短信或邮箱验证码')]
    #[Payload(field: 'tel_code', type: FieldEnum::STRING, name: '区号', required: false, desc: '手机号区号')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '登录信息', desc: '用户登录信息', children: [
        new ResultData(field: 'token', type: FieldEnum::STRING, name: '访问令牌', desc: '用户访问令牌'),
        new ResultData(field: 'expires', type: FieldEnum::INT, name: '过期时间', desc: '令牌过期时间戳')
    ], root: true)]
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody() ?: [], [
            "username" => ["required", "请输入账号"],
        ]);
        $types = ['password', 'code'];
        $userType = Member::getUserType($data->username);
        $model = MemberUser::query();
        if ($userType == 'tel') {
            if ($data['tel_code']) {
                $model->where('tel_code', $data['tel_code']);
            } else {
                $model->whereNull('tel_code');
            }
            $type = in_array($data->type, $types) ? $data->type : 'code';
        }
        if ($userType == 'email') {
            $model->where('email', $data->username);
            $type = in_array($data->type, $types) ? $data->type : 'password';
        }
        $info = $model->first();
        if (!$info) {
            throw new ExceptionBusiness("该账号未注册");
        }
        if (!$info->status) {
            throw new ExceptionBusiness("该账号已被注销");
        }

        $config = Config::getJsonValue('member');
        if ($userType == 'email') {
            if (!$config['email_login']) {
                throw new ExceptionBusiness("系统未开启邮箱登录");
            }

            if ($type === 'password') {
                if (!$info->password) {
                    throw new ExceptionBusiness("请输入登录密码");
                }
            }
            if ($type === 'code') {
                if (!$config['code_login']) {
                    throw new ExceptionBusiness("系统未开启验证码登录");
                }
                if (!$data->code) {
                    throw new ExceptionBusiness("请输入验证码");
                }
                if (!$this->debug) {
                    Sms::verify($data->username, $data->code);
                }
            }
        }
        if ($userType == 'tel') {
            if (!$config['tel_login']) {
                throw new ExceptionBusiness("系统未开启手机号登录");
            }

            if ($type === 'password') {
                if (!$data->password) {
                    throw new ExceptionBusiness("请输入密码");
                }
                if ($info && !$info->password) {
                    throw new ExceptionBusiness("该账号只能用验证码登录");
                }
                if (!password_verify($data->password, $info->password)) {
                    throw new ExceptionBusiness("账号或密码错误");
                }
            }
            if ($type === 'code') {
                if (!$config['code_login']) {
                    throw new ExceptionBusiness("系统未开启验证码登录");
                }
                if (!$data->code) {
                    throw new ExceptionBusiness("请输入验证码");
                }
                if (!$this->debug) {
                    Sms::verify($data->username, $data->code);
                }
            }
        }
        return send($response, "ok", Member::Login($info->id));
    }


    #[Route(methods: 'GET', route: '/member/check', app: 'api')]
    #[Api(name: '检查用户', payloadExample: ['username' => '13800138000', 'tel_code' => '+86'])]
    #[Query(field: 'username', type: FieldEnum::STRING, name: '用户名', desc: '手机号或邮箱')]
    #[Query(field: 'tel_code', type: FieldEnum::STRING, name: '区号', required: false, desc: '手机号区号')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '检查结果', desc: '用户是否存在', children: [
        new ResultData(field: 'check', type: FieldEnum::BOOL, name: '是否存在', desc: '用户是否已注册')
    ], root: true)]
    public function check(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getQueryParams() ?: [], [
            "username" => ["required", "请输入账号"],
        ]);
        $userType = Member::getUserType($data->username);
        $model = MemberUser::query();
        if ($userType == 'tel') {
            $model->where('tel', $data->username);
            $telCode = urlencode($data->tel_code);
            if ($telCode && $telCode != '+86') {
                $model->where('tel_code', $telCode);
            } else {
                $model->whereNull('tel_code');
            }
        }
        if ($userType == 'email') {
            $model->where('email', $data->username);
        }

        $check = $model->exists();
        return send($response, 'ok', [
            'check' => $check
        ]);
    }

    #[Route(methods: 'POST', route: '/member/register', app: 'api')]
    #[Api(name: '注册', payloadExample: ['username' => '13800138000', 'password' => 'password123', 'code' => '123456', 'tel_code' => '+86'])]
    #[Payload(field: 'username', type: FieldEnum::STRING, name: '用户名', desc: '手机号或邮箱')]
    #[Payload(field: 'password', type: FieldEnum::STRING, name: '密码', required: false, desc: '用户密码')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '验证码', required: false, desc: '短信或邮箱验证码')]
    #[Payload(field: 'tel_code', type: FieldEnum::STRING, name: '区号', required: false, desc: '手机号区号')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '注册信息', desc: '用户注册信息', children: [
        new ResultData(field: 'token', type: FieldEnum::STRING, name: '访问令牌', desc: '用户访问令牌'),
        new ResultData(field: 'expires', type: FieldEnum::INT, name: '过期时间', desc: '令牌过期时间戳')
    ], root: true)]
    public function register(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody() ?: [], [
            "username" => ["required", "请输入账号"],
        ]);
        $userType = Member::getUserType($data->username);

        $config = Config::getJsonValue('member');
        if (!$config['register']) {
            throw new ExceptionBusiness("该账号已被注册");
        }

        $model = MemberUser::query();
        if ($userType == 'tel') {
            $model->where('tel', $data->username);
            if ($data['tel_code']) {
                $model->where('tel_code', $data['tel_code']);
            } else {
                $model->whereNull('tel_code');
            }
        }
        if ($userType == 'email') {
            $model->where('email', $data->username);
        }
        $info = $model->first();
        if ($info) {
            throw new ExceptionBusiness("该账号已被注册");
        }

        if ($userType == 'tel' && $config['code_login']) {
            if (!$data->code) {
                throw new ExceptionBusiness("请输入验证码");
            }
            if ($data['tel_code']) {
                Sms::verify(new PhoneNumber($data->tel, $data['tel_code']), $data->code);
            } else {
                Sms::verify($data->tel, $data->code);
            }
        }

        if ($userType == 'email' && $config['code_login']) {
            if (!$data->code) {
                throw new ExceptionBusiness("请输入验证码");
            }
            Email::verify($data->username, $data->code);
        }

        $password = '';
        if ($data->password) {
            $password = $data->password;
        }

        if (!$password && !$data->code) {
            throw new ExceptionBusiness("请输入注册密码");
        }

        if ($userType == 'tel') {
            $uid = Member::Register(telCode: $data->tel_code, tel: $data->username, password: $password, params: $data);
        }
        if ($userType == 'email') {
            $uid = Member::Register(email: $data->username, password: $password, params: $data);
        }
        return send($response, "ok", Member::Login($uid));
    }

    #[Route(methods: 'POST', route: '/member/forget', app: 'api')]
    #[Api(name: '重置密码', payloadExample: ['username' => '13800138000', 'password' => 'newpassword123', 'code' => '123456', 'tel_code' => '+86'])]
    #[Payload(field: 'username', type: FieldEnum::STRING, name: '用户名', desc: '手机号或邮箱')]
    #[Payload(field: 'password', type: FieldEnum::STRING, name: '新密码', desc: '用户新密码')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '验证码', desc: '短信或邮箱验证码')]
    #[Payload(field: 'tel_code', type: FieldEnum::STRING, name: '区号', required: false, desc: '手机号区号')]
    public function forget(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody() ?: [], [
            "username" => ["required", "请输入账号"],
        ]);

        $userType = Member::getUserType($data->username);

        $model = MemberUser::query();
        if ($userType == 'tel') {
            $model->where('tel', $data->username);
        }
        if ($userType == 'email') {
            $model->where('email', $data->username);
        }
        $info = $model->first();

        if (!$info) {
            throw new ExceptionBusiness("该账号不存在");
        }

        if ($userType == 'tel') {
            if (!$data->code) {
                throw new ExceptionBusiness("请输入验证码");
            }
            if ($data['tel_code']) {
                Sms::verify(new PhoneNumber($data->username, $data->tel_code), $data->tel_code);
            } else {
                Sms::verify($data->tel, $data->code);
            }
        }

        if ($userType == 'email') {
            if (!$data->code) {
                throw new ExceptionBusiness("请输入验证码");
            }
            Email::verify($data->username, $data->code);
        }

        $password = password_hash($data->password, PASSWORD_DEFAULT);
        $info->password = $password;
        $info->save();
        return send($response, "修改密码成功");
    }

    #[Route(methods: 'GET', route: '/member/code', app: 'api')]
    #[Api(name: '验证码', payloadExample: ['username' => '13800138000', 'tel_code' => '+86'])]
    #[Query(field: 'username', type: FieldEnum::STRING, name: '用户名', desc: '手机号或邮箱')]
    #[Query(field: 'tel_code', type: FieldEnum::STRING, name: '区号', required: false, desc: '手机号区号')]
    public function code(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getQueryParams() ?: [], [
            "username" => ["required", "请输入账号"],
        ]);
        $userType = Member::getUserType($data->username);
        $config = Config::getJsonValue('member');

        if ($userType == 'tel') {
            if ($data['tel_code']) {
                if (!$config['sms_global_tpl']) {
                    throw new ExceptionBusiness('请设置验证码模板');
                }
                Sms::code((int)$config['sms_global_tpl'], new PhoneNumber($data->username, $data['tel_code']));
            } else {
                if (!$config['sms_tpl']) {
                    throw new ExceptionBusiness('请设置验证码模板');
                }
                Sms::code((int)$config['sms_tpl'], $data->username);
            }
        }
        if ($userType == 'email') {
            if (!$config['email_tpl']) {
                throw new ExceptionBusiness('请设置验证码模板');
            }
            Email::code((int)$config['email_tpl'], $data->username);
        }
        return send($response, '验证码已发送，请注意查收！');
    }

    #[Route(methods: 'GET', route: '/member/agreement', app: 'api')]
    #[Api(name: '协议')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '协议内容', desc: '用户协议内容', children: [
        new ResultData(field: 'content', type: FieldEnum::STRING, name: '协议内容', desc: '用户协议HTML内容')
    ], root: true)]
    public function agreement(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $config = Config::getJsonValue('member');
        $content = $config['agreement'];
        return send($response, 'ok', [
            'content' => $content
        ]);
    }

    #[Route(methods: 'GET', route: '/member/privacy', app: 'api')]
    #[Api(name: '隐私')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '隐私政策', desc: '隐私政策内容', children: [
        new ResultData(field: 'content', type: FieldEnum::STRING, name: '政策内容', desc: '隐私政策HTML内容')
    ], root: true)]
    public function privacy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $config = Config::getJsonValue('member');
        $content = $config['privacy'];
        return send($response, 'ok', [
            'content' => $content
        ]);
    }

    #[Route(methods: 'GET', route: '/member/about', app: 'api')]
    #[Api(name: '关于')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '关于信息', desc: '关于我们信息', children: [
        new ResultData(field: 'content', type: FieldEnum::STRING, name: '关于内容', desc: '关于我们HTML内容'),
        new ResultData(field: 'tel', type: FieldEnum::STRING, name: '客服电话', desc: '客服联系电话'),
        new ResultData(field: 'qrcode', type: FieldEnum::STRING, name: '客服二维码', desc: '客服二维码图片')
    ], root: true)]
    public function about(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $config = Config::getJsonValue('member');
        $content = $config['about'];
        $tel = $config['service_tel'];
        $qrcode = $config['service_qrcode'];
        return send($response, 'ok', [
            'content' => $content,
            'tel' => $tel,
            'qrcode' => $qrcode
        ]);
    }

    #[Route(methods: 'GET', route: '/member/info', app: 'apiMember')]
    #[Api(name: '用户信息')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '用户信息', desc: '当前登录用户信息', root: true)]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $data = Member::getUserInfo((int)$auth['id']);
        return send($response, 'ok', $data);
    }

}