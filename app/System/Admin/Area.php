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

        $files = $request->getUploadedFiles();
        $file = $files['file'];

        $tempFile = tempnam(sys_get_temp_dir(), 'upload');
        $file->moveTo($tempFile);

        $data = (new FastExcel)->import($tempFile);

        // Map both the legacy Chinese headers and the new English headers.
        $columnMap = [
            'province_name' => ['NAME_PROV', '省'],
            'province_code' => ['CODE_PROV', '省编码'],
            'city_name' => ['NAME_CITY', '市'],
            'city_code' => ['CODE_CITY', '市编码'],
            'district_name' => ['NAME_COUN', '区县'],
            'district_code' => ['CODE_COUN', '区县编码'],
            'town_name' => ['NAME_TOWN', '乡镇街道'],
            'town_code' => ['CODE_TOWN', '乡镇街道编码'],
        ];

        $extract = static function (array $row, array $keys): string {
            foreach ($keys as $key) {
                if (array_key_exists($key, $row)) {
                    $value = trim((string)$row[$key]);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
            return '';
        };

        $newData = [];
        foreach ($data as $key => $vo) {
            $provinceName = $extract($vo, $columnMap['province_name']);
            $provinceCode = $extract($vo, $columnMap['province_code']);
            $cityName = $extract($vo, $columnMap['city_name']);
            $cityCode = $extract($vo, $columnMap['city_code']);
            $districtName = $extract($vo, $columnMap['district_name']);
            $districtCode = $extract($vo, $columnMap['district_code']);
            $townName = $extract($vo, $columnMap['town_name']);
            $townCode = $extract($vo, $columnMap['town_code']);

            // 处理省级数据
            if ($provinceName !== '' && $provinceCode !== '') {
                $provinceKey = $provinceCode . ':1';
                if (!isset($newData[$provinceKey])) {
                    $newData[$provinceKey] = [
                        'parent_code' => 0,
                        'code' => $provinceCode,
                        'name' => $provinceName,
                        'level' => 1,
                        'leaf' => true,
                    ];
                }
            }

            // 处理市级数据
            if ($cityName !== '' && $cityCode !== '') {
                $cityKey = $cityCode . ':2';
                if (!isset($newData[$cityKey])) {
                    $newData[$cityKey] = [
                        'parent_code' => $provinceCode,
                        'code' => $cityCode,
                        'name' => $cityName,
                        'level' => 2,
                        'leaf' => true,
                    ];
                    // 更新省级为非叶子节点
                    if ($provinceCode !== '' && isset($newData[$provinceCode . ':1'])) {
                        $newData[$provinceCode . ':1']['leaf'] = false;
                    }
                }
            }

            // 处理区县数据
            if ($districtName !== '' && $districtCode !== '') {
                $districtKey = $districtCode . ':3';
                if (!isset($newData[$districtKey])) {
                    $newData[$districtKey] = [
                        'parent_code' => $cityCode,
                        'code' => $districtCode,
                        'name' => $districtName,
                        'level' => 3,
                        'leaf' => true,
                    ];
                    // 更新市级为非叶子节点
                    if ($cityCode !== '' && isset($newData[$cityCode . ':2'])) {
                        $newData[$cityCode . ':2']['leaf'] = false;
                    }
                }
            }

            // 处理乡镇街道数据
            if ($townName !== '' && $townCode !== '') {
                $townKey = $townCode . ':4';
                if (!isset($newData[$townKey])) {
                    $newData[$townKey] = [
                        'parent_code' => $districtCode,
                        'code' => $townCode,
                        'name' => $townName,
                        'level' => 4,
                        'leaf' => true,
                    ];
                    // 更新区县为非叶子节点
                    if ($districtCode !== '' && isset($newData[$districtCode . ':3'])) {
                        $newData[$districtCode . ':3']['leaf'] = false;
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
