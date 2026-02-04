<?php

namespace App\Data\Api;

use App\Data\Models\Data as ModelsData;
use App\Data\Models\DataConfig;
use App\Data\Service\Config;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Validator\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'web', route: '/data/{name}', name: 'data')]
#[Docs(name: '公开数据集', category: '数据')]
class Data
{
    public $api = false;

    private function config($name)
    {
        $config = DataConfig::query()->where('label', $name)->first();
        if (!$config) {
            throw new ExceptionBusiness('数据集不存在');
        }
        if ($config->api_sign && !$this->api) {
            throw new ExceptionBusiness('请使用 API 接口访问数据');
        }

        return $config;
    }

    #[Route(methods: 'GET', route: '')]
    #[Api(name: '数据列表', payloadExample: ['limit' => 20])]
    #[Query(field: 'limit', type: FieldEnum::INT, name: '分页大小', required: false, desc: '每页记录数，默认20')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '数据列表', desc: '数据集列表数据，根据不同数据集配置返回不同结构', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();

        $query = ModelsData::query();

        $config = $this->query($query, $request, $args, false);

        if (!$config->api_list) {
            throw new ExceptionBusiness('数据集不支持列表查询');
        }

        Config::filter($query, $config, $params);

        switch ($config->table_type) {
            case 'tree':
                $list = $query->get()->toTree();
                break;
            case 'list':
                $list = $query->get();
                break;
            default:
                $list = $query->paginate($params['limit'] ?: 20);
        }

        $data = format_data($list, function ($item) {
            return $item->transform();
        });
        return send($response, 'ok', ...$data);
    }

    #[Route(methods: 'GET', route: '/{id}')]
    #[Api(name: '数据详情')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '数据ID', required: true)]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '数据详情', desc: '数据集详细信息，根据不同数据集配置返回不同结构', root: true)]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $query = ModelsData::query();

        $config = $this->query($query, $request, $args, false);

        if (!$config->api_info) {
            throw new ExceptionBusiness('数据集不支持详情查询');
        }

        $info = $query->find($id);
        if (!$info) {
            throw new ExceptionBusiness('数据不存在');
        }

        $data = format_data($info, function ($item) {
            return $item->transform();
        });
        return send($response, 'ok', ...$data);
    }

    #[Route(methods: 'POST', route: '')]
    #[Api(name: '创建数据', payloadExample: ['field1' => 'value1', 'field2' => 'value2'])]
    #[Payload(field: 'data', type: FieldEnum::OBJECT, name: '数据内容', desc: '数据内容，根据不同数据集配置字段不同')]
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $config = $this->config($args['name']);

        $jwt = self::decode($request);
        if ($config->api_user && !$jwt) {
            throw new ExceptionBusiness('Authorization error', 401);
        }

        if (!$config->api_create) {
            throw new ExceptionBusiness('数据集不支持创建');
        }

        $data = Validator::parser($request->getParsedBody(), []);
        $data = Config::format($data, $config);

        // 保存来源 IP
        $data['ip'] = function_exists('get_ip') ? get_ip() : ($request->getServerParams()['REMOTE_ADDR'] ?? '');

        // 绑定用户信息（如需）
        
        if ($jwt) {
            $data['has_type'] = $jwt['sub'];
            $data['has_id'] = $jwt['id'];
        }

        // 去重：相同数据不允许重复提交
        if ($config->post_retry) {
            $dupQuery = ModelsData::query()->where('config_id', $data['config_id']);
            if (!empty($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $k => $v) {
                    $dupQuery->where("data->$k", $v);
                }
            }
            if ($dupQuery->exists()) {
                throw new ExceptionBusiness('请勿重复提交');
            }
        }

        // 限流：X 分钟窗口内 X 条
        $limit = (int)($config->post_limit ?? 0);
        if ($limit > 0) {
            $minutes = (int)($config->post_window ?? 1);
            if ($minutes < 1) { $minutes = 1; }
            $startTime = date('Y-m-d H:i:s', time() - ($minutes * 60));
            $limitQuery = ModelsData::query()
                ->where('config_id', $data['config_id'])
                ->where('created_at', '>=', $startTime);

            switch ((int)($config->post_tactics ?? 0)) {
                case 1: // 按 IP
                    $limitQuery->where('ip', $data['ip'] ?? '');
                    break;
                case 2: // 按用户
                    if ($jwt) {
                        $limitQuery->where('has_type', $jwt['sub'])->where('has_id', $jwt['id']);
                    } else {
                        // 未登录时按 IP 限流
                        $limitQuery->where('ip', $data['ip'] ?? '');
                    }
                    break;
                case 0: // 整体
                default:
                    // 不附加维度条件
                    break;
            }

            $count = $limitQuery->count();
            if ($count >= $limit) {
                throw new ExceptionBusiness('提交过于频繁，请稍后再试');
            }
        }

        ModelsData::query()->create($data);

        return send($response, 'ok');
    }

    #[Route(methods: 'PUT', route: '/{id}')]
    #[Api(name: '更新数据', payloadExample: ['field1' => 'new_value1', 'field2' => 'new_value2'])]
    #[Params(field: 'id', type: FieldEnum::INT, name: '数据ID', required: true)]
    #[Payload(field: 'data', type: FieldEnum::OBJECT, name: '数据内容', desc: '要更新的数据内容，根据不同数据集配置字段不同')]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $query = ModelsData::query();

        $config = $this->query($query, $request, $args, true);

        if (!$config->api_update) {
            throw new ExceptionBusiness('数据集不支持更新');
        }

        $info = $query->find($id);
        if (!$info) {
            throw new ExceptionBusiness('数据不存在');
        }

        $data = Validator::parser($request->getParsedBody(), []);
        $info->update(Config::format($data, $config));

        return send($response, 'ok');
    }

    #[Route(methods: 'DELETE', route: '/{id}')]
    #[Api(name: '删除数据')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '数据ID', required: true)]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $id = (int)$args['id'];
        $query = ModelsData::query();

        $config = $this->query($query, $request, $args, true);

        if (!$config->api_delete) {
            throw new ExceptionBusiness('数据集不支持删除');
        }

        $info = $query->find($id);
        if (!$info) {
            throw new ExceptionBusiness('数据不存在');
        }

        $info->delete();

        return send($response, 'ok');
    }

    private function query($query, ServerRequestInterface $request, array $args, bool $force = false)
    {
        $config = $this->config($args['name']);

        $query->where('config_id', $config->id);

        $jwt = self::decode($request);
        if ($config->api_user && !$jwt) {
            throw new ExceptionBusiness('Authorization error', 401);
        }

        if (($config->api_user && $config->api_user_self) || $force) {
            $query->where('has_type', $jwt['sub'])->where('has_id', $jwt['id']);
        }

        Config::has($query, $config);

        return $config;
    }

    private function decode(ServerRequestInterface $request): ?array
    {
        $jwtStr = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        try {
            $jwt = JWT::decode($jwtStr, new Key(App::config("use")->get("app.secret"), 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
        if (!$jwt->sub || !$jwt->id) {
            return null;
        }
        return (array)$jwt;
    }

}