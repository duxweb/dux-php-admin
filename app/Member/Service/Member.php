<?php

namespace App\Member\Service;

use App\Member\Event\RegisterEvent;
use App\Member\Event\UserEvent;
use App\Member\Models\MemberLevel;
use App\Member\Models\MemberUser;
use App\System\Service\Config;
use Core\App;
use Core\Auth\Auth;
use Core\Handlers\ExceptionBusiness;
use Core\Validator\Data;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Cache\InvalidArgumentException;
use const FILTER_VALIDATE_EMAIL;

class Member
{

    /**
     * 账户注册
     * @param string $nickname
     * @param string $tel
     * @param string $email
     * @param string $password
     * @param Data|null $params
     * @return int
     * @throws InvalidArgumentException
     * @throws PhpfastcacheSimpleCacheException
     */
    public static function Register(string $nickname = '', ?string $telCode = '', string $tel = '', string $email = '', string $password = '', ?Data $params = null): int
    {
        if (!$tel && !$email) {
            throw new ExceptionBusiness('请输入账号');
        }
        // 修复手机号前后特殊字符问题
        if ($tel) {
            $telCode = trim($telCode);
            $tel = trim($tel);
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $phoneNumber = $phoneUtil->parse(($telCode ?: '+86') . $tel);
            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                throw new ExceptionBusiness('手机号格式错误');
            }
        }

        $info = MemberUser::query()->where(function ($query) use ($telCode, $tel, $email) {
            if ($tel) {
                $query->orWhere(function ($query) use ($telCode, $tel) {
                    $query->where('tel_code', $telCode)->where('tel', $tel);
                });
            }
            if ($email) {
                $query->orWhere('email', $tel);
            }
        })->first();
        if ($info) {
            throw new ExceptionBusiness('该账号已注册');
        }
        $key = 'user.register.' . ($tel ?: $email);
        $lock = App::lock()->createLock($key);
        if (!$lock->acquire()) {
            throw new ExceptionBusiness('业务繁忙，请稍后再试');
        }
        $config = Config::getJsonValue('member');
        try {
            $info = new MemberUser();
            if ($nickname) {
                $info->nickname = $nickname;
            }
            if ($tel) {
                $info->tel = $tel;
            }
            if ($email) {
                $info->email = $email;
            }
            if ($password) {
                $info->password = password_hash($password, PASSWORD_DEFAULT);
            }
            // 默认等级
            $info->level_id = $config['default_level'];
            // 默认头像
            $info->avatar = $config['default_avatar'];
            $info->save();
            if (!$info->nickname) {
                $info->nickname = "默认昵称$info->id";
            }
            $info->save();

            // 注册接口
            // NOTE member.register
            App::event()->dispatch(new RegisterEvent($info, $params), 'member.register');
        } finally {
            $lock->release();
        }
        return $info->id;
    }

    /**
     * 账户登录
     * @param int $userId
     * @return array
     */
    public static function Login(int $userId): array
    {
        $info = self::getUserInfo($userId);
        if (!$info['status']) {
            throw new ExceptionBusiness("该用户已禁用");
        }

        $ip = get_ip();
        try {
            $address = App::geo()?->search($ip) ?: '';
            [$country, $null, $province, $city] = explode('|', $address);
        }catch (\Exception $e) {}

        MemberUser::query()->where('id', $info['id'])->update([
            'login_ip' => $ip,
            'login_country' => $country,
            'login_province' => $province,
            'login_city' => $city,
        ]);

        $token = Auth::token("member", [
                'id' => $userId,
                'password' => $info['password']
            ], 2592000);

        unset($info['password']);
        unset($info['status']);

        return [
            "userInfo" => $info,
            "token" => $token
        ];
    }

    /**
     * 获取用户资料
     * @param int $userId
     * @return array
     */
    public static function getUserInfo(int $userId): array
    {
        $info = MemberUser::query()->find($userId);
        if (empty($info)) return [];

        $event = new UserEvent($info);
        $event->setData($info->transform());

        // NOTE member.user.info (用户资料信息)
        App::event()->dispatch($event, 'member.user.info');
        return $event->getData();
    }

    /**
     * 获取账户类型
     * @param $username
     * @return string
     */
    public static function getUserType($username, ?string $telCode = ''): string
    {
        if (empty($username)) {
            throw new ExceptionBusiness('请输入账号');
        }

        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $type = 'tel';
        } else {
            $type = 'email';
        }
        switch ($type) {
            case 'tel':
                $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
                $phoneNumber = $phoneUtil->parse(($telCode ?: '+86') . $username);
                if (!$phoneUtil->isValidNumber($phoneNumber)) {
                    throw new ExceptionBusiness('手机号格式错误');
                }
                break;
            case 'email' :
                if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    throw new ExceptionBusiness('邮箱账号不正确!');
                }
                break;
        }
        return $type;
    }
}