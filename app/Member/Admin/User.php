<?php

declare(strict_types=1);

namespace App\Member\Admin;

use App\Member\Event\RegisterEvent;
use App\Member\Models\MemberUser;
use App\System\Service\Config;
use Core\App;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/member/user', name: 'member.user')]
class User extends Resources
{
    protected string $model = MemberUser::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();
        $search = $params["keyword"];
        if ($search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where("nickname", "like", "%$search%");
                $query->orWhere("tel", $search);
                $query->orWhere("email", $search);
            });
        }
        if ($params['level_id']) {
            $query->where('level_id', $params['level_id']);
        }
    }

    public function transform(object $item): array
    {
        $item = $item->transform();
        unset($item['password']);
        return $item;
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "nickname" => ["required", "昵称不能为空"],
            "tel" => ["required", "手机号不能为空"],
            "level_id" => ["required", "用户等级不能为空"],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        $id = $args['id'];
        $model = MemberUser::query()->where('tel', $data->tel);
        if ($id) {
            $model->where("id", "<>", $id);
        }
        if ($model->exists()) {
            throw new ExceptionBusiness("该手机号已存在");
        }
        $saveData = [
            "level_id" => $data->level_id,
            "nickname" => $data->nickname,
            "tel" => $data->tel,
            "tel_code" => $data->tel_code ?: null,
            "email" => $data->email,
            "avatar" => $data->avatar,
            "sex" => $data->sex,
            "birthday" => $data->birthday,
            "province" => $data->area[0],
            "city" => $data->area[1],
            "district" => $data->area[2],
            "introduction" => $data->introduction,
            "cover" => $data->cover,
            "info" => $data->info,
            "status" => $data->status,
        ];
        // 默认头像
        if (empty($saveData['avatar'])) {
            $config = Config::getJsonValue('member');
            $saveData['avatar'] = $config['default_avatar'];
        }

        if ($data->password) {
            $saveData["password"] = password_hash($data->password, PASSWORD_BCRYPT);
        }
        return $saveData;
    }

    public function createAfter(Data $data, $info): void
    {
        // NOTE member.register （用户注册事件）
        App::event()->dispatch(new RegisterEvent($info), 'member.register');
    }
}
