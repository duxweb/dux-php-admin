<?php

namespace App\System\Extends;

use App\System\Models\SystemFile;
use App\System\Service\Storage;
use App\System\Service\Upload as ServiceUpload;
use Core\Handlers\ExceptionBusiness;
use Psr\Http\Message\ServerRequestInterface;

class Upload
{
    public function uploadSign(string $filename, string $mime = '', ?int $size = 0, string $driver = '', string $prefix = ''): array
    {
        $pathInfo = ServiceUpload::generatePath($filename, $mime, $prefix);

        ServiceUpload::validateFile($pathInfo['ext'], $size);

        $object = Storage::getObject($driver);
        $data = $object->signPostUrl($pathInfo['path']);

        $data['params'] = [
            ...$data['params'],
            'key' => $pathInfo['path'],
            'Content-Type' => $pathInfo['mime'],
        ];

        // 针对傻逼腾讯兼容特殊处理
        if (str_contains($data['url'], '.myqcloud.com')) {
            foreach ($data['params'] as $key => $value) {
                $key = str_replace('X-Amz-', 'X-cos-', $key);
                $data['params'][$key] = $value;
            }
        }

        $data['uploadUrl'] = $data['url'];
        $data['url'] = $object->publicUrl($pathInfo['path']);

        return $data;
    }

    public function uploadStorage(string $hasType, ServerRequestInterface $request, ?bool $manager = false, ?string $mime = '', ?string $driver = '', ?string $folder = '', ?string $prefix = '')
    {
        $file = $this->validateUploadedFile($request);
        $filename = $file->getClientFilename();
        $fileSize = $file->getSize();

        $this->validateFileBasics($filename, $fileSize);

        $resource = $this->getFileResource($file);
        $pathInfo = ServiceUpload::generatePathContent($resource, $filename, $mime, $prefix);

        ServiceUpload::validateFile($pathInfo['ext'], $fileSize);
        rewind($resource);

        $object = Storage::getObject($driver);
        $object->writeStream($pathInfo['path'], $resource);

        return $manager
            ? $this->save($driver, $pathInfo['path'], $pathInfo['name'], $pathInfo['ext'], (int)$fileSize, $pathInfo['mime'], $hasType, (int)$folder)
            : $this->buildFileResponse($object->publicUrl($pathInfo['path']), $pathInfo['name'], $fileSize, $pathInfo['mime'], $pathInfo['ext']);
    }

    public function uploadSave(string $hasType, ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        return $this->save(
            driver: $data['driver'],
            path: $data['path'] ?? '',
            name: $data['name'] ?? '',
            ext: $data['ext'] ?? '',
            size: (int)($data['size'] ?? 0),
            mime: $data['mime'] ?? '',
            hasType: $hasType,
            folder: (int)($data['folder'] ?? 0)
        );
    }

    private function save($driver, string $path, string $name, string $ext, int $size, string $mime, ?string $hasType = '', ?int $folder = null)
    {
        $this->validateSaveParams($path, $name, $ext, $size, $mime);

        $object = Storage::getObject($driver ?: '');

        if (!$object->exists($path)) {
            throw new ExceptionBusiness('文件不存在');
        }

        if ($object->size($path) != $size) {
            throw new ExceptionBusiness('文件大小不匹配');
        }

        $url = $object->publicUrl($path);

        $model = SystemFile::create([
            'dir_id' => $folder ?: null,
            'has_type' => $hasType,
            'driver' => $driver,
            'url' => $url,
            'path' => $path,
            'name' => $name,
            'ext' => $ext,
            'size' => $size,
            'mime' => $mime,
        ]);

        return $this->buildFileResponse($url, $name, $size, $mime, $ext, $model->id);
    }

    private function validateUploadedFile(ServerRequestInterface $request)
    {
        $file = $request->getUploadedFiles()['file'] ?? null;
        if (!$file) {
            throw new ExceptionBusiness('File not found');
        }
        return $file;
    }

    private function validateFileBasics(?string $filename, ?int $fileSize): void
    {
        if (!$filename) {
            throw new ExceptionBusiness('文件名不能为空');
        }
        if (!$fileSize) {
            throw new ExceptionBusiness('文件大小为0');
        }
    }

    private function getFileResource($file)
    {
        $stream = $file->getStream();
        $resource = $stream->detach();

        if ($resource === null) {
            throw new ExceptionBusiness('无法获取文件流资源');
        }

        return $resource;
    }

    private function validateSaveParams(string $path, string $name, string $ext, int $size, string $mime): void
    {
        $params = compact('path', 'name', 'ext', 'size', 'mime');
        $messages = [
            'path' => '路径不能为空',
            'name' => '文件名不能为空',
            'ext' => '扩展名不能为空',
            'size' => '文件大小不能为空',
            'mime' => 'MIME类型不能为空',
        ];

        foreach ($params as $key => $value) {
            if (empty($value)) {
                throw new ExceptionBusiness($messages[$key]);
            }
        }
    }

    private function buildFileResponse(string $url, string $filename, int $filesize, string $filetype, string $fileext, ?int $id = null): array
    {
        $response = [
            'url' => $url,
            'filename' => $filename,
            'filesize' => $filesize,
            'filetype' => $filetype,
            'fileext' => $fileext,
            'time' => now()->toDateTimeString(),
        ];

        if ($id !== null) {
            $response['id'] = $id;
        }

        return $response;
    }
}
