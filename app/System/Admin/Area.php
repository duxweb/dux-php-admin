<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\SystemArea;
use Core\App;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rap2hpoutre\FastExcel\FastExcel;
use Exception;

ini_set('memory_limit', '1024M');

#[Resource(app: 'admin',  route: '/system/area', name: 'system.area', actions: ['list', 'delete'])]
class Area extends Resources
{
    protected string $model = SystemArea::class;

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "code" => $item->code,
            "name" => $item->name,
            "level" => $item->level,
        ];
    }

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args)
    {

        $params = $request->getQueryParams();
        $keyword = $params['keyword'];

        if($keyword) {
            $query->where('name', $keyword);
        }
    }

    #[Action(methods: 'POST', route: '/import', name: 'import')]
    public function import(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $data = $request->getParsedBody();

        $files = $request->getUploadedFiles();
        $file = $files['file'];

        $tempFile = tempnam(sys_get_temp_dir(), 'upload');
        $file->moveTo($tempFile);

        $data = (new FastExcel)->import($tempFile);

        $newData = [];
        foreach ($data as $key => $vo) {
            // 处理省级数据
            if (!empty($vo['省']) && !empty($vo['省编码'])) {
                $provinceKey = $vo['省编码'] . ':1';
                if (!isset($newData[$provinceKey])) {
                    $newData[$provinceKey] = [
                        'parent_code' => 0,
                        'code' => $vo['省编码'],
                        'name' => $vo['省'],
                        'level' => 1,
                        'leaf' => true,
                    ];
                }
            }

            // 处理市级数据
            if (!empty($vo['市']) && !empty($vo['市编码'])) {
                $cityKey = $vo['市编码'] . ':2';
                if (!isset($newData[$cityKey])) {
                    $newData[$cityKey] = [
                        'parent_code' => $vo['省编码'],
                        'code' => $vo['市编码'],
                        'name' => $vo['市'],
                        'level' => 2,
                        'leaf' => true,
                    ];
                    // 更新省级为非叶子节点
                    if (isset($newData[$vo['省编码'] . ':1'])) {
                        $newData[$vo['省编码'] . ':1']['leaf'] = false;
                    }
                }
            }

            // 处理区县数据
            if (!empty($vo['区县']) && !empty($vo['区县编码'])) {
                $districtKey = $vo['区县编码'] . ':3';
                if (!isset($newData[$districtKey])) {
                    $newData[$districtKey] = [
                        'parent_code' => $vo['市编码'],
                        'code' => $vo['区县编码'],
                        'name' => $vo['区县'],
                        'level' => 3,
                        'leaf' => true,
                    ];
                    // 更新市级为非叶子节点
                    if (isset($newData[$vo['市编码'] . ':2'])) {
                        $newData[$vo['市编码'] . ':2']['leaf'] = false;
                    }
                }
            }

            // 处理乡镇街道数据
            if (!empty($vo['乡镇街道']) && !empty($vo['乡镇街道编码'])) {
                $townKey = $vo['乡镇街道编码'] . ':4';
                if (!isset($newData[$townKey])) {
                    $newData[$townKey] = [
                        'parent_code' => $vo['区县编码'],
                        'code' => $vo['乡镇街道编码'],
                        'name' => $vo['乡镇街道'],
                        'level' => 4,
                        'leaf' => true,
                    ];
                    // 更新区县为非叶子节点
                    if (isset($newData[$vo['区县编码'] . ':3'])) {
                        $newData[$vo['区县编码'] . ':3']['leaf'] = false;
                    }
                }
            }
        }
        $list = array_chunk(collect(array_values($newData))->sortBy('code')->toArray(), 1000);

        App::db()->getConnection()->statement('SET FOREIGN_KEY_CHECKS = 0');
        App::db()->getConnection()->table('system_area')->truncate();
        App::db()->getConnection()->statement('SET FOREIGN_KEY_CHECKS = 1');

        App::db()->getConnection()->beginTransaction();
        try {
            foreach ($list as $vo) {
                App::db()->getConnection()->table('system_area')->insert(array_values($vo));
            }
            App::db()->getConnection()->commit();
        } catch (Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }
        return send($response, "导入成功");
    }
}
