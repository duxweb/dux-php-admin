<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Data\Models\DataConfig;
use App\System\Models\SystemMenu;
use Core\App;
use Core\Database\Attribute\AutoMigrate;
use Core\Handlers\ExceptionBusiness;
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
    private const SYSTEM_FIELDS = ['id', 'parent_id', 'config_id', 'has_type', 'has_id', 'created_at', 'updated_at'];

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
            'id_sort' => $item->id_sort,
            'post_retry' => $item->post_retry,
            'post_limit' => (int) $item->post_limit,
            'post_window' => (int) $item->post_window,
            'post_tactics' => (int) $item->post_tactics,
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
            "table_type" => $data->table_type ?? 0,
            "form_type" => $data->form_type ?? 0,
            'id_sort' => $data->id_sort ?: 'asc',
            'post_retry' => !!$data->post_retry,
            'post_limit' => (int) ($data->post_limit ?? 0),
            'post_window' => (int) ($data->post_window ?? 1),
            'post_tactics' => (int) ($data->post_tactics ?? 0),
            'api_sign' => $data->api_sign ?? 0,
            'api_user' => $data->api_user ?? 0,
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
                ["required", "数据标识不能为空"],
                [function ($field, $value) {
                    return $value !== 'data' && $value !== 'config';
                }, '数据标识不支持 data 和 config'],
                [function ($field, $value, $params, $fields) use ($args) {
                    $model = DataConfig::query()->where('label', $fields['label']);
                    if ($args['id']) {
                        $model->where("id", "<>", $args['id']);
                    }
                    return !$model->exists();
                }, '数据标识已存在']
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

        $formData = $data['data'] ?? [];
        $info->form_data = $formData;
        $info->field_data = $this->buildFieldDataFromForm(
            is_array($info->field_data) ? $info->field_data : [],
            is_array($formData) ? $formData : []
        );
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

    #[Action(methods: 'GET', route: '/models')]
    public function models(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $models = [];
        $attributes = App::attributes();
        
        foreach ($attributes as $attribute) {
            if (empty($attribute['annotations'])) {
                continue;
            }
            
            foreach ($attribute['annotations'] as $annotation) {
                if ($annotation['name'] === AutoMigrate::class) {
                    $className = $attribute['class'];
                    $modelName = substr($className, strrpos($className, '\\') + 1);

                    $models[] = [
                        'label' => $modelName,
                        'value' => $className,
                    ];
                    break;
                }
            }
        }
        
        usort($models, function($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        
        return send($response, 'ok', $models);
    }

    #[Action(methods: 'GET', route: '/{id}/field')]
    public function fieldDesign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);
        return send($response, 'ok', $info->field_data);
    }

    #[Action(methods: 'PUT', route: '/{id}/field')]
    public function fieldSave(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        $id = (int) $args['id'];
        $info = DataConfig::query()->find($id);

        $this->validateFieldData($data['data'] ?? []);
        foreach ($data['has'] ?? [] as $vo) {
            if ($vo['name'] === 'has' || $vo['name'] === 'config') {
                throw new ExceptionBusiness('关联配置不能使用保留名');
            }
        }

        $info->field_data = $data;
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
        $parentInfo = SystemMenu::query()->where('name', 'data')->first();

        $listMenu = [
            'app' => 'admin',
            'parent_id' => $parentInfo->id,
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

    #[Action(methods: 'GET', route: '/{id}/share')]
    public function share(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);
        $info = DataConfig::query()->find($id);
        if (!$info) {
            throw new ExceptionBusiness('数据集不存在');
        }

        $payload = [
            'version' => 1,
            'type' => 'data_config_share',
            'created_at' => date('Y-m-d H:i:s'),
            'config' => [
                'name' => (string)$info->name,
                'label' => (string)$info->label,
                'table_type' => (string)$info->table_type,
                'form_type' => (string)$info->form_type,
                'id_sort' => (string)$info->id_sort,
                'post_retry' => (bool)$info->post_retry,
                'post_limit' => (int)$info->post_limit,
                'post_window' => (int)$info->post_window,
                'post_tactics' => (int)$info->post_tactics,
                'api_sign' => (bool)$info->api_sign,
                'api_user' => (bool)$info->api_user,
                'api_user_self' => (bool)$info->api_user_self,
                'api_list' => (bool)$info->api_list,
                'api_info' => (bool)$info->api_info,
                'api_create' => (bool)$info->api_create,
                'api_update' => (bool)$info->api_update,
                'api_delete' => (bool)$info->api_delete,
                'table_data' => is_array($info->table_data) ? $info->table_data : [],
                'form_data' => is_array($info->form_data) ? $info->form_data : [],
                'field_data' => is_array($info->field_data) ? $info->field_data : [],
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$json) {
            throw new ExceptionBusiness('分享数据生成失败');
        }

        return send($response, 'ok', [
            'name' => (string)$info->name,
            'label' => (string)$info->label,
            'code' => base64_encode(base64_encode($json)),
        ]);
    }

    #[Action(methods: 'POST', route: '/import')]
    public function import(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();
        $params = is_array($params) ? $params : [];
        $code = trim((string)($params['code'] ?? ''));
        if ($code === '') {
            throw new ExceptionBusiness('请粘贴分享数据');
        }

        $payload = $this->decodeSharePayload($code);
        $config = $payload['config'] ?? null;
        if (!is_array($config)) {
            throw new ExceptionBusiness('分享数据格式错误');
        }

        $name = trim((string)($config['name'] ?? ''));
        $label = trim((string)($config['label'] ?? ''));
        if ($name === '' || $label === '') {
            throw new ExceptionBusiness('分享数据缺少名称或标识');
        }
        if ($label === 'data' || $label === 'config') {
            throw new ExceptionBusiness('数据标识不支持 data 和 config');
        }
        if (DataConfig::query()->where('label', $label)->exists()) {
            throw new ExceptionBusiness('数据标识已存在');
        }

        $tableType = (string)($config['table_type'] ?? 'pages');
        if (!in_array($tableType, ['list', 'pages', 'tree'], true)) {
            $tableType = 'pages';
        }
        $formType = (string)($config['form_type'] ?? 'modal');
        if (!in_array($formType, ['modal', 'drawer', 'page'], true)) {
            $formType = 'modal';
        }
        $idSort = (string)($config['id_sort'] ?? 'asc');
        if (!in_array($idSort, ['asc', 'desc'], true)) {
            $idSort = 'asc';
        }
        $postWindow = (int)($config['post_window'] ?? 1);
        if ($postWindow < 1) {
            $postWindow = 1;
        }
        $postTactics = (int)($config['post_tactics'] ?? 0);
        if (!in_array($postTactics, [0, 1, 2], true)) {
            $postTactics = 0;
        }

        $item = DataConfig::query()->create([
            'name' => $name,
            'label' => $label,
            'table_type' => $tableType,
            'form_type' => $formType,
            'id_sort' => $idSort,
            'post_retry' => (bool)($config['post_retry'] ?? false),
            'post_limit' => (int)($config['post_limit'] ?? 0),
            'post_window' => $postWindow,
            'post_tactics' => $postTactics,
            'api_sign' => (bool)($config['api_sign'] ?? false),
            'api_user' => (bool)($config['api_user'] ?? false),
            'api_user_self' => (bool)($config['api_user_self'] ?? false),
            'api_list' => (bool)($config['api_list'] ?? false),
            'api_info' => (bool)($config['api_info'] ?? false),
            'api_create' => (bool)($config['api_create'] ?? false),
            'api_update' => (bool)($config['api_update'] ?? false),
            'api_delete' => (bool)($config['api_delete'] ?? false),
            'table_data' => is_array($config['table_data'] ?? null) ? $config['table_data'] : [],
            'form_data' => is_array($config['form_data'] ?? null) ? $config['form_data'] : [],
            'field_data' => is_array($config['field_data'] ?? null) ? $config['field_data'] : [],
        ]);

        return send($response, '导入成功', $this->transform($item));
    }

    private function buildFieldDataFromForm(array $fieldData, array $formData): array
    {
        $has = is_array($fieldData['has'] ?? null) ? $fieldData['has'] : [];
        $oldData = is_array($fieldData['data'] ?? null) ? $fieldData['data'] : [];
        $oldMap = [];

        foreach ($oldData as $item) {
            if (!is_array($item) || empty($item['field'])) {
                continue;
            }
            $oldMap[$item['field']] = $item;
        }

        $generatedData = $this->extractFormFields($formData);
        $this->validateFieldData($generatedData);

        $resultData = [];
        foreach ($generatedData as $item) {
            $field = $item['field'];
            $row = array_merge([
                'name' => '',
                'field' => $field,
                'type' => 'string',
                'default_value' => null,
                'length' => null,
                'sort' => false,
                'filter' => false,
                'where' => '=',
            ], $oldMap[$field] ?? [], $item);
            $row['type'] = $this->normalizeFieldType($row['type'] ?? null);
            $resultData[] = $row;
        }

        return [
            'data' => $resultData,
            'has' => $has,
        ];
    }

    private function decodeSharePayload(string $code): array
    {
        $raw = trim($code);
        if ($raw === '') {
            throw new ExceptionBusiness('请粘贴分享数据');
        }

        $candidates = [$raw];
        $base64 = preg_replace('/\s+/', '', $raw);
        if ($base64) {
            $first = base64_decode($base64, true);
            if ($first !== false) {
                $candidates[] = $first;
                $second = base64_decode($first, true);
                if ($second !== false) {
                    $candidates[] = $second;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            $json = json_decode($candidate, true);
            if (is_array($json)) {
                return $json;
            }
        }

        throw new ExceptionBusiness('分享数据格式错误');
    }

    private function extractFormFields(array $formData): array
    {
        $fieldMap = [];
        $fieldSet = [];
        $nodes = $formData['data'] ?? [];
        if (!is_array($nodes)) {
            return [];
        }

        $this->walkFormNodes($nodes, $fieldMap, $fieldSet);
        return array_values($fieldMap);
    }

    private function walkFormNodes(array $nodes, array &$fieldMap, array &$fieldSet): void
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            if (isset($node['name']) && is_string($node['name'])) {
                $options = is_array($node['options'] ?? null) ? $node['options'] : [];
                $field = trim((string) ($options['name'] ?? $options['field'] ?? ''));
                if ($field) {
                    if (isset($fieldSet[$field])) {
                        throw new ExceptionBusiness('字段名不能重复: ' . $field);
                    }
                    $fieldSet[$field] = true;
                    $fieldMap[$field] = [
                        'name' => (string) ($options['label'] ?? $field),
                        'field' => $field,
                        'type' => $this->detectFieldType($node['name'], $options),
                        'default_value' => $this->detectDefaultValue($options),
                    ];
                }

                $children = $node['children'] ?? [];
                if (is_array($children)) {
                    $component = strtolower(trim($node['name']));
                    if (str_starts_with($component, 'dux-')) {
                        $component = substr($component, 4);
                    }
                    // 动态输入器内部字段属于 JSON 子项，不写入 field_data
                    if ($component !== 'dynamic-input') {
                        $this->walkFormNodes($children, $fieldMap, $fieldSet);
                    }
                }
                continue;
            }

            // grid 等嵌套布局会出现二维 children，这里递归拍平处理
            $this->walkFormNodes($node, $fieldMap, $fieldSet);
        }
    }

    private function detectFieldType(string $component, array $options): string
    {
        $component = strtolower(trim($component));
        if (str_starts_with($component, 'dux-')) {
            $component = substr($component, 4);
        }
        $attr = is_array($options['attr'] ?? null) ? $options['attr'] : [];

        if (in_array($component, ['input-number', 'sider'], true)) {
            return 'decimal';
        }

        if ($component === 'switch') {
            return 'boolean';
        }

        if (in_array($component, ['checkbox', 'dynamic-input', 'dynamic-tags', 'transfer-async', 'region'], true)) {
            return 'json';
        }

        if (in_array($component, ['file-upload', 'image-upload', 'select', 'cascader', 'tree-select', 'select-async', 'cascader-async', 'tree-select-async'], true)) {
            return !empty($attr['multiple']) ? 'json' : 'string';
        }

        if ($component === 'date') {
            $dateType = (string) ($attr['type'] ?? 'date');
            if (str_contains($dateType, 'range')) {
                return 'json';
            }
            return $dateType === 'datetime' ? 'datetime' : 'date';
        }

        if ($component === 'time') {
            $timeType = (string) ($attr['type'] ?? '');
            return str_contains($timeType, 'range') ? 'json' : 'string';
        }

        return 'string';
    }

    private function detectDefaultValue(array $options): mixed
    {
        if (array_key_exists('defaultValue', $options)) {
            return $options['defaultValue'];
        }

        $attr = is_array($options['attr'] ?? null) ? $options['attr'] : [];
        if (array_key_exists('defaultValue', $attr)) {
            return $attr['defaultValue'];
        }

        if (array_key_exists('value', $options)) {
            return $options['value'];
        }

        return null;
    }

    private function normalizeFieldType(mixed $type): string
    {
        $allowTypes = ['string', 'int', 'decimal', 'boolean', 'datetime', 'date', 'json'];
        return in_array($type, $allowTypes, true) ? $type : 'string';
    }

    private function validateFieldData(array $fields): void
    {
        $fieldSet = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $name = trim((string) ($field['field'] ?? ''));
            if (!$name) {
                throw new ExceptionBusiness('字段名不能为空');
            }
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                throw new ExceptionBusiness('字段名仅支持字母、数字和下划线，且不能以数字开头');
            }
            if (in_array($name, self::SYSTEM_FIELDS, true)) {
                throw new ExceptionBusiness('字段名不能使用系统保留字');
            }
            if (isset($fieldSet[$name])) {
                throw new ExceptionBusiness('字段名不能重复: ' . $name);
            }
            $fieldSet[$name] = true;
        }
    }
}
