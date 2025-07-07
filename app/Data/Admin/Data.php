<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Data\Models\Data as ModelsData;
use App\Data\Models\DataConfig;
use App\Data\Service\Config;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data as ValidatorData;
use Core\Validator\Validator;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/data/data/{name}', name: 'data.data')]
class Data extends Resources
{
	protected string $model = ModelsData::class;
    protected object $config;

    public function init(ServerRequestInterface $request, ResponseInterface $response, array $args): void {
        $name = $args['name'];
        $config = DataConfig::query()->where('label', $name)->first();
        if (!$config) {
            throw new \Exception("数据配置不存在");
        }
        $this->config = $config;
        if ($config->table_type === 'tree') {
            $this->tree = true;
            $this->pagination['status'] = false;
        }
        if ($config->table_type === 'list') {
            $this->pagination['status'] = false;
        }
    }

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();

        Config::filter($query, $this->config, $params);
    }

    public function transform(object $item): array
    {
        return $item->transform();
    }

    public function format(ValidatorData $data, ServerRequestInterface $request, array $args): array
    {
        $data = Validator::parser($request->getParsedBody(), []);
        return Config::format($data, $this->config);
    }

}
