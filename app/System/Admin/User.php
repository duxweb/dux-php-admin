<?php

namespace App\System\Admin;

use App\System\Models\SystemDept;
use App\System\Models\SystemUser;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/user', name: 'system.user')]
class User extends Resources
{

    protected string $model = SystemUser::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();

        if ($params['keyword']) {
            $query->where('nickname', 'like', '%' . $params['keyword'] . '%');
        }

        switch ($params['tab']) {
            case 1:
                $query->where('status', 1);
                break;
            case 2:
                $query->where('status', 0);
                break;
        }


        if ($params['dept_id']) {
            $deptList = SystemDept::query()->with(['descendants'])->where('id', $params['dept_id'])->get();
            $deptIds = [];
            $tops = [];
            foreach ($deptList as $vo) {
                $deptIds[] = $vo['id'];
                $deptIds = [...$deptIds, ...$vo->descendants->pluck('id')];
                if ($vo->tops) {
                    $tops = [...$tops, ...$vo->tops];
                }
            }
            $query->whereIn('dept_id', $deptIds);
        }
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "username" => $item->username,
            "nickname" => $item->nickname,
            "avatar" => $item->avatar,
            "status" => (bool)$item->status,
            'role_id' => $item->role_id,
            'dept_id' => $item->dept_id,
            'role_name' => $item->role->name,
            'dept_name' => $item->dept->name,
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "nickname" => ["required", '昵称不能为空'],
            "username" => [
                ["required", '用户名不能为空'],
                [function ($field, $value, $params, $fields) use ($args) {
                    $model = SystemUser::query()->where('username', $fields['username']);
                    if ($args['id']) {
                        $model->where("id", "<>", $args['id']);
                    }
                    return !$model->exists();
                }, '用户名已存在']
            ],
            "password" => ["requiredWithout", "id", '密码不能为空'],
            "role_id" => ["required", '角色不能为空'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        $formatData = [
            "nickname" => $data->nickname,
            "username" => $data->username,
            "avatar" => $data->avatar,
            "status" => $data->status,
            "dept_id" => $data->dept_id,
            "role_id" => $data->role_id,
        ];
        if ($data->password) {
            $formatData['password'] = function ($value) {
                return password_hash($value, PASSWORD_BCRYPT);
            };
        }
        return $formatData;
    }
}
