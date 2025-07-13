<?php

namespace App\Member\Api;

use App\Member\Models\MemberUser;
use App\Member\Service\Member;
use App\Send\Service\Email;
use App\Send\Service\Sms;
use App\System\Service\Config;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Docs\Enum\PayloadTypeEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Validator\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Overtrue\EasySms\PhoneNumber;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/setting')]
#[Docs(name: '用户设置')]
class Setting
{
    #[Route(methods: 'GET', route: '/info')]
    #[Api(name: '设置信息')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '用户信息', desc: '用户设置信息', root: true)]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = Member::getUserInfo((int)$auth['id']);
        unset($userInfo->password);
        return send($response, "ok", $userInfo);
    }

    #[Route(methods: 'POST', route: '/switch')]
    #[Api(name: '开关', payloadExample: ['key' => 'notification', 'value' => true])]
    #[Payload(field: 'key', type: FieldEnum::STRING, name: '开关名称', desc: '设置项的键名')]
    #[Payload(field: 'value', type: FieldEnum::STRING, name: '开关值', desc: '设置项的值')]
    public function switch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $data = Validator::parser($request->getParsedBody(), [
            'key' => ['required', '请输入开关名称'],
            'value' => ['required', '请输入开关值'],
        ]);

        $info = MemberUser::query()->find($auth['id']);
        $setting = $info->setting ?: [];
        $setting[$data->key] = $data->value;

        MemberUser::query()->where('id', $auth['id'])->update([
            'setting' => $setting
        ]);

        return send($response, "设置成功");
    }

    #[Route(methods: 'POST', route: '/avatar')]
    #[Api(name: '头像', payloadType: PayloadTypeEnum::MULTIPART)]
    #[Payload(field: 'avatar', type: FieldEnum::FILE, name: '头像文件', desc: '用户头像文件')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '头像信息', desc: '修改头像结果', children: [
        new ResultData(field: 'url', type: FieldEnum::STRING, name: '头像URL', desc: '新头像的访问地址')
    ], root: true)]
    public function avatar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $uploads = $request->getUploadedFiles();
        $url = '';
        $manager = new ImageManager(Driver::class);
        foreach ($uploads as $key => $vo) {
            $stream = $manager->read($vo->getStream()->getContents())->resize(120, 120);
            $basename = bin2hex(random_bytes(10));
            $path = '/avatar/' . $basename . '.jpg';
            App::storage()->write($path, $stream->toJpeg(80)->toString());
            $url = App::storage()->publicUrl($path);
            break;
        }
        MemberUser::query()->where('id', $auth['id'])->update([
            'avatar' => $url
        ]);
        return send($response, "修改头像成功", [
            'url' => $url
        ]);
    }

    #[Route(methods: 'POST', route: '/data')]
    #[Api(name: '个人资料', payloadExample: ['nickname' => '昵称', 'sex' => 1, 'birthday' => '1990-01-01', 'area' => ['广东省', '深圳市', '南山区']])]
    #[Payload(field: 'nickname', type: FieldEnum::STRING, name: '昵称', desc: '用户昵称')]
    #[Payload(field: 'sex', type: FieldEnum::INT, name: '性别', required: false, desc: '性别：0-未知，1-男，2-女')]
    #[Payload(field: 'birthday', type: FieldEnum::STRING, name: '生日', required: false, desc: '生日日期')]
    #[Payload(field: 'area', type: FieldEnum::ARRAY, name: '地区', required: false, desc: '省市区数组')]
    #[Payload(field: 'introduction', type: FieldEnum::STRING, name: '介绍', required: false, desc: '用户个人介绍')]
    #[Payload(field: 'info', type: FieldEnum::OBJECT, name: '扩展资料', required: false, desc: '自定义的扩展资料提交')]
    public function data(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody(), [
            "nickname" => ["required", "请输入昵称"],
            "birthday" => [
                ["date", "请输入生日"],
                ["optional"],
            ],
        ]);

        $auth = $request->getAttribute('auth');
        $userData = [
            'nickname' => $data->nickname,
            'sex' => (int)$data->sex,
            'province' => $data->area[0],
            'city' => $data->area[1],
            'district' => $data->area[2],
        ];
        if (isset($data->birthday)) {
            $userData['birthday'] = $data->birthday;
        }
        if (isset($data->introduction)) {
            $userData['introduction'] = $data->introduction;
        }
        if (isset($data->info)) {
            $userData['info'] = $data->info;
        }

        MemberUser::query()->where('id', $auth['id'])->update($userData);

        return send($response, "修改资料成功");
    }

    #[Route(methods: 'PUT', route: '/setting')]
    #[Api(name: '资料设置', payloadExample: ['key' => 'nickname', 'value' => '新昵称'])]
    #[Payload(field: 'key', type: FieldEnum::STRING, name: '设置名', desc: '设置个人信息字段名')]
    #[Payload(field: 'value', type: FieldEnum::STRING, name: '设置值', desc: '设置个人信息字段值')]
    public function setting(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody(), [
            "key" => ["required", "请输入设置名"],
            "value" => ["required", "请输入设置值"],
        ]);

        $auth = $request->getAttribute('auth');

        $allowedFields = ['nickname', 'sex', 'birthday', 'introduction', 'area'];

        if (!in_array($data->key, $allowedFields)) {
            throw new ExceptionBusiness('不允许设置此字段');
        }

        $userData = [];

        if ($data->key === 'area') {
            $userData['province'] = $data->value[0];
            $userData['city'] = $data->value[1];
            $userData['district'] = $data->value[2];
        } else {
            $userData[$data->key] = $data->value;
        }

        MemberUser::query()->where('id', $auth['id'])->update($userData);

        return send($response, "修改资料成功");
    }

    #[Route(methods: 'GET', route: '/code')]
    #[Api(name: '验证码', payloadExample: ['type' => 'tel'])]
    #[Query(field: 'type', type: FieldEnum::STRING, name: '验证码类型', desc: 'tel或email')]
    public function code(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);

        $params = $request->getQueryParams();
        $type = $params['type'] == 'email' ? 'email' : 'tel';

        if ($type == 'tel' && !$userInfo->tel) {
            throw new ExceptionBusiness('未设置手机号码');
        }
        if ($type == 'email' && !$userInfo->email) {
            throw new ExceptionBusiness('未设置邮箱账号');
        }
        $config = Config::getJsonValue('member');
        if ($type == 'tel') {
            if ($userInfo->tel_code) {
                if (!$config['sms_global_tpl']) {
                    throw new ExceptionBusiness('请设置验证码模板');
                }
                Sms::code((int)$config['sms_global_tpl'], new PhoneNumber($userInfo->tel, $userInfo->tel_code));
            } else {
                if (!$config['sms_tpl']) {
                    throw new ExceptionBusiness('请设置验证码模板');
                }
                Sms::code((int)$config['sms_tpl'], $userInfo->tel);
            }
        }
        if ($type == 'email') {
            if (!$config['email_tpl']) {
                throw new ExceptionBusiness('请设置验证码模板');
            }
            Email::code((int)$config['email_tpl'], $userInfo->email, 2);
        }

        return send($response, '验证码已发送，请注意查收！');
    }

    #[Route(methods: 'POST', route: '/password')]
    #[Api(name: '密码', payloadExample: ['password' => 'newpassword123', 'code' => '123456', 'type' => 'tel'])]
    #[Payload(field: 'password', type: FieldEnum::STRING, name: '新密码', desc: '用户新密码')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '验证码', desc: '短信或邮箱验证码')]
    #[Payload(field: 'type', type: FieldEnum::STRING, name: '验证类型', required: false, desc: 'tel或email')]
    public function password(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);

        $data = Validator::parser($request->getParsedBody(), [
            "password" => ["required", "请输入新密码"],
            "code" => ["required", "请输入验证码"],
        ]);

        if (!$data->code) {
            throw new ExceptionBusiness("请输入验证码");
        }
        $type = $data['type'] == 'email' ? 'email' : 'tel';

        if ($type == 'tel' && !$userInfo->tel) {
            throw new ExceptionBusiness('未设置手机号码');
        }
        if ($type == 'email' && !$userInfo->email) {
            throw new ExceptionBusiness('未设置邮箱账号');
        }

        if ($type == 'tel') {
            if ($userInfo->tel_code) {
                Sms::verify(new PhoneNumber($userInfo->tel, $userInfo->tel_code), $data->code, 2);
            } else {
                Sms::verify($userInfo->tel, $data->code, 2);
            }
        }
        if ($type == 'email') {
            Email::verify($userInfo->email, $data->code, 2);
        }

        MemberUser::query()->where('id', $auth['id'])->update([
            'password' => password_hash($data->password, PASSWORD_DEFAULT)
        ]);

        return send($response, "修改密码成功");
    }

    #[Route(methods: 'POST', route: '/bindTel')]
    #[Api(name: '绑定手机', payloadExample: ['tel' => '13800138000', 'code' => '123456'])]
    #[Payload(field: 'tel', type: FieldEnum::STRING, name: '手机号', desc: '要绑定的手机号码')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '验证码', desc: '手机短信验证码')]
    public function bindTel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);
        if ($userInfo->tel) {
            throw new ExceptionBusiness("已绑定手机号");
        }
        $data = Validator::parser($request->getParsedBody(), [
            "tel" => ["required", "请输入手机号码"],
            "code" => ["required", "请输入验证码"],
        ]);
        $exists = MemberUser::query()->where('tel', $data->tel)->exists();
        if ($exists) {
            throw new ExceptionBusiness("该手机号码已被绑定");
        }

        if ($userInfo->tel_code) {
            Sms::verify(new PhoneNumber($data->tel, $userInfo->tel_code), $data->code, 2);
        } else {
            Sms::verify($data->tel, $data->code, 2);
        }

        MemberUser::query()->where('id', $auth['id'])->update([
            'tel' => $data->tel,
        ]);

        return send($response, "绑定手机号成功");
    }

    #[Route(methods: 'POST', route: '/bindEmail')]
    #[Api(name: '绑定邮箱', payloadExample: ['email' => 'user@example.com', 'code' => '123456'])]
    #[Payload(field: 'email', type: FieldEnum::STRING, name: '邮箱', desc: '要绑定的邮箱地址')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '验证码', desc: '邮箱验证码')]
    public function bindEmail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);
        if ($userInfo->email) {
            throw new ExceptionBusiness("已绑定邮箱");
        }

        $data = Validator::parser($request->getParsedBody(), [
            "email" => ["required", "请输入邮箱"],
            "code" => ["required", "请输入验证码"],
        ]);
        $exists = MemberUser::query()->where('email', $data->email)->exists();
        if ($exists) {
            throw new ExceptionBusiness("该邮箱已被绑定");
        }
        Email::verify($data->email, $data->code, 2);

        MemberUser::query()->where('id', $auth['id'])->update([
            'email' => $data->email
        ]);

        return send($response, "绑定邮箱成功");
    }

    #[Route(methods: 'POST', route: '/replaceTel')]
    #[Api(name: '更换手机', payloadExample: ['tel' => '13800138001', 'code' => '123456', 'original_code' => '654321', 'tel_code' => '+86'])]
    #[Payload(field: 'tel', type: FieldEnum::STRING, name: '新手机号', desc: '新的手机号码')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '新验证码', desc: '新手机号验证码')]
    #[Payload(field: 'original_code', type: FieldEnum::STRING, name: '原验证码', desc: '原手机号验证码')]
    #[Payload(field: 'tel_code', type: FieldEnum::STRING, name: '区号', required: false, desc: '手机号区号')]
    public function replaceTel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);
        if (!$userInfo->tel) {
            throw new ExceptionBusiness('未设置手机号码');
        }
        $data = Validator::parser($request->getParsedBody(), [
            'tel' => ['required', '请输入更换手机号码'],
            'code' => ['required', '请输入更换验证码'],
            'original_code' => ['required', '请输入原验证码'],
        ]);
        if ($userInfo->tel == $data->tel) {
            throw new ExceptionBusiness('原手机号码与新手机号码相同');
        }
        $exists = MemberUser::query()->where('tel', $data->tel)->exists();
        if ($exists) {
            throw new ExceptionBusiness("该手机号码已被绑定");
        }

        if ($userInfo->tel_code) {
            Sms::verify(new PhoneNumber($userInfo->tel, $userInfo->tel_code), $data->original_code, 2);
        } else {
            Sms::verify($userInfo->tel, $data->original_code, 2);
        }

        if ($data->tel_code) {
            Sms::verify(new PhoneNumber($data->tel, $data->tel_code), $data->code, 1);
        } else {
            Sms::verify($data->tel, $data->code, 1);
        }

        MemberUser::query()->where('id', $auth['id'])->update([
            'tel' => $data->tel,
        ]);

        return send($response, "绑定手机号成功");
    }

    #[Route(methods: 'POST', route: '/replaceEmail')]
    #[Api(name: '更换邮箱', payloadExample: ['email' => 'newuser@example.com', 'code' => '123456', 'original_code' => '654321'])]
    #[Payload(field: 'email', type: FieldEnum::STRING, name: '新邮箱', desc: '新的邮箱地址')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '新验证码', desc: '新邮箱验证码')]
    #[Payload(field: 'original_code', type: FieldEnum::STRING, name: '原验证码', desc: '原邮箱验证码')]
    public function replaceEmail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);
        if (!$userInfo->email) {
            throw new ExceptionBusiness('未设置邮箱账号');
        }
        $data = Validator::parser($request->getParsedBody(), [
            'email' => ['required', '请输入更换邮箱账号'],
            'code' => ['required', '请输入更换验证码'],
            'original_code' => ['required', '请输入原验证码'],
        ]);
        if ($userInfo->email == $data->email) {
            throw new ExceptionBusiness('原邮箱与新邮箱相同');
        }
        $exists = MemberUser::query()->where('email', $data->email)->exists();
        if ($exists) {
            throw new ExceptionBusiness("该邮箱已被绑定");
        }

        Email::verify($userInfo->email, $data->original_code, 2);
        Email::verify($data->email, $data->code, 1);

        MemberUser::query()->where('id', $auth['id'])->update([
            'email' => $data->email,
        ]);

        return send($response, "绑定邮箱成功");
    }

    #[Route(methods: 'POST', route: '/disable')]
    #[Api(name: '注销账户')]
    public function disable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userInfo = MemberUser::query()->find($auth['id']);
        if (!$userInfo) {
            throw new ExceptionBusiness("账号不存在");
        }
        MemberUser::query()->where('id', $auth['id'])->update([
            'status' => 0
        ]);
        return send($response, "注销账户成功");
    }
}