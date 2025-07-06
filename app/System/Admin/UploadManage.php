<?php

namespace App\System\Admin;

use App\System\Models\SystemFile;
use App\System\Models\SystemFileDir;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/uploadManager')]
class UploadManage extends \App\System\Extends\Upload
{

    #[Route(methods: 'GET', route: '')]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();

        $folder = $params['folder'] ?? '';

        $dirInfo = SystemFileDir::query()->where('id', $folder)->first();

        $dirQuery = SystemFileDir::query()->where('has_type', 'admin');

        if ($folder) {
            $dirQuery->where('parent_id', $folder);
        } else {
            $dirQuery->whereNull('parent_id');
        }
        $dirList = $dirQuery->orderBy('id', 'desc')->get();

        $fileQuery = SystemFile::query()->where('has_type', 'admin');

        if ($folder) {
            $fileQuery->where('dir_id', $folder);
        } else {
            $fileQuery->whereNull('dir_id');
        }

        switch ($params['type']) {
            case 'image':
                $fileQuery->where('mime', 'like', 'image%');
                break;
            case 'video':
                $fileQuery->where(function ($query) {
                    $query->where('mime', 'like', 'video%')->orWhere('mime', 'like', 'audio%');
                });
                break;
            case 'docs':
                $fileQuery->whereIn('mime', ["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation"]);
                break;
        }

        $driver = $params['driver'] ?? '';
        if ($driver && $driver != 'all') {
            $fileQuery->where('driver', $driver);
        }

        $fileList = $fileQuery->orderBy('id', 'desc')->paginate(20);


        $data = [];

        if ($params['page'] <= 1) {
            foreach ($dirList as $dir) {
                $data[] = [
                    'id' => $dir->id,
                    'type' => 'folder',
                    'filename' => $dir->name,
                    'time' => $dir->created_at->toDateTimeString(),
                ];
            }
        }


        ['data' => $files, 'meta' => $meta] = format_data($fileList, function ($item) {
            return [
                'id' => $item->id,
                'url' => $item->url,
                'type' => 'file',
                'filename' => $item->name,
                'filesize' => human_filesize($item->size),
                'filetype' => $item->mime,
                'time' => $item->created_at->toDateTimeString(),
            ];
        });

        $data = [...$data, ...($files ?: [])];

        $meta['folder'] = $dirInfo->parent_id;

        $crumbs = SystemFileDir::query()->ancestorsAndSelf($dirInfo->id)->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
            ];
        })->reverse()->toArray();

        $meta['crumbs'] = $crumbs;

        return send($response, "ok", $data, $meta);
    }

    #[Route(methods: 'POST', route: '')]
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();

        $folder = $data['folder'] ?? '';
        $name = $data['name'] ?? '';

        if (!$name) {
            return send($response, "请输入文件夹名称");
        }

        $dir = new SystemFileDir();
        $dir->has_type = 'admin';
        $dir->name = $name;
        $dir->parent_id = $folder;
        $dir->save();

        return send($response, "ok");
    }

    #[Route(methods: 'PUT', route: '')]
    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();

        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $type = $data['type'] ?? '';

        if (!$name) {
            return send($response, "请输入文件夹名称");
        }

        if ($type == 'file') {
            SystemFile::query()->where('id', $id)->update(['name' => $name]);
        } else {
            SystemFileDir::query()->where('id', $id)->update(['name' => $name]);
        }

        return send($response, "ok");
    }

    #[Route(methods: 'DELETE', route: '/batch')]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();

        $type = $data['type'] ?? '';
        $data = $data['data'] ?? [];

        if (!$data) {
            return send($response, "请选择要操作的文件");
        }

        if ($type == 'file') {
            SystemFile::query()->whereIn('id', $data)->delete();
        } else {
            $allDirIds = [];
            foreach ($data as $dirId) {
                $dirIds = SystemFileDir::query()
                    ->descendantsAndSelf($dirId)
                    ->pluck('id')
                    ->toArray();
                $allDirIds = array_merge($allDirIds, $dirIds);
            }
            $allDirIds = array_unique($allDirIds);
            SystemFile::query()->whereIn('dir_id', $allDirIds)->delete();
            SystemFileDir::query()->whereIn('id', $allDirIds)->delete();
        }

        return send($response, "ok");
    }

}
