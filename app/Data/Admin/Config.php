<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Data\Models\DataConfig;
use App\System\Models\SystemMenu;
use Core\App;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/data/config', name: 'data.config')]
class Config extends Resources
{
	protected string $model = DataConfig::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();
        $keyword = $params['keyword'] ?? '';
        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        $query->orderBy('id');
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "name" => $item->name,
            "label" => $item->label,
            "type" => $item->type,
            "table_type" => $item->table_type,
            "form_type" => $item->form_type,
            'api_sign' => $item->api_sign,
            'api_user' => $item->api_user,
            'api_user_self' => $item->api_user_self,
            'api_list' => $item->api_list,
            'api_info' => $item->api_info,
            "api_create" => $item->api_create,
            "api_update" => $item->api_update,
            "api_delete" => $item->api_delete,
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => $data->name,
            "label" => $data->label,
            "table_type" => $data->table_type,
            "form_type" => $data->form_type,
            'api_sign' => $data->api_sign,
            'api_user' => $data->api_user,
            'api_user_self' => $data->api_user_self,
            'api_list' => $data->api_list,
            'api_info' => $data->api_info,
            "api_create" => $data->api_create,
            "api_update" => $data->api_update,
            "api_delete" => $data->api_delete,
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => ["required", "数据名称不能为空"],
            "label" => [
                ["required", "数据标签不能为空"],
                 [function ($field, $value, $params, $fields) use ($args) {
                    $model = DataConfig::query()->where('label', $fields['label']);
                    if ($args['id']) {
                        $model->where("id", "<>", $args['id']);
                    }
                    return !$model->exists();
                }, '数据标签已存在']
            ],
        ];
    }

    #[Action(methods: 'GET', route: '/{name}/config')]
    public function config(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = $args['name'];
        $info = DataConfig::query()->where('label', $name)->first();
        return send($response, 'ok', $info);
    }

    #[Action(methods: 'GET', route: '/{id}/form')]
    public function formDesign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);
        return send($response, 'ok', $info->form_data);
    }

    #[Action(methods: 'PUT', route: '/{id}/form')]
    public function formSave(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);

        $info->form_data = $data['data'];
        $info->save();
        return send($response, 'ok');
    }

    #[Action(methods: 'GET', route: '/{id}/table')]
    public function tableDesign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);
        return send($response, 'ok', $info->table_data);
    }

    #[Action(methods: 'PUT', route: '/{id}/table')]
    public function tableSave(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);

        $info->table_data = $data;
        $info->save();
        return send($response, 'ok');
    }

    #[Action(methods: 'POST', route: '/{id}/menu')]
    public function menu(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);

        $prefix = 'data.' . $info->label;

        $menuInfo = SystemMenu::query()->with(['parent'])->where('name', $prefix . '.list')->first();

        $listMenu = [
            'app' => 'admin',
            'label' => $info->name,
            'label_lang' => $prefix . '.list',
            'name' => $prefix . '.list',
            'path' => 'data/' . $info->label,
            'loader' => 'Data/Data/table',
            'type' => 'menu',
            'buttons' => [
                [
                    'label' => '详情',
                    'name' => $prefix . '.show',
                    'label_lang' => $prefix . '.show',
                ],
                [
                    'label' => '创建',
                    'name' => $prefix . '.create',
                    'label_lang' => $prefix . '.create',
                ],
                [
                    'label' => '编辑',
                    'name' => $prefix . '.edit',
                    'label_lang' => $prefix . '.edit',
                ],
                [
                    'label' => '更新',
                    'name' => $prefix . '.store',
                    'label_lang' => $prefix . '.store',
                ],
                [
                    'label' => '删除',
                    'name' => $prefix . '.delete',
                    'label_lang' => $prefix . '.delete',
                ],
            ]
        ];

        if ($info->form_type === 'page') {
            $listMenu['children'] = [
                [
                    'app' => 'admin',
                    'label' => '创建',
                    'label_lang' => $prefix . '.create',
                    'name' => $prefix . '.create',
                    'path' => 'data/' . $info->label . '/create',
                    'loader' => 'Data/Data/page',
                    'type' => 'menu',
                    'hidden' => 1,
                ],
                [
                    'app' => 'admin',
                    'label' => '编辑',
                    'label_lang' => $prefix . '.edit',
                    'name' => $prefix . '.edit',
                    'path' => 'data/' . $info->label . '/edit/:id',
                    'loader' => 'Data/Data/page',
                    'type' => 'menu',
                    'hidden' => 1,
                ]
            ];
        }

        try {
            App::db()->getConnection()->beginTransaction();

            if ($menuInfo) {
                $menuInfo->delete();
            }

            SystemMenu::create($listMenu, $menuInfo->parent);

            App::db()->getConnection()->commit();

        }catch(\Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }

        SystemMenu::clearMenu('admin');
        return send($response, '菜单生成成功');
    }

}
