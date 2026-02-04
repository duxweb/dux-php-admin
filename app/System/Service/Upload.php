<?php

namespace App\System\Service;

use Core\Handlers\ExceptionBusiness;
use Illuminate\Support\Str;

class Upload
{

    private const BLACK_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'pht',
        'jsp', 'asp', 'aspx', 'cer', 'asa', 'cdx',
        'js', 'vbs', 'bat', 'cmd', 'com', 'exe', 'scr', 'msi',
        'sh', 'py', 'pl', 'rb', 'jar', 'class',
        'htaccess', 'htpasswd', 'ini', 'dll', 'so'
    ];

    public static function getUploadConfig(): array
    {
        $uploadConfig = \App\System\Service\Config::getJsonValue('system', []);
        // Default allow-list if system.upload_ext is not configured.
        // Keep it broad enough for common admin uploads, but still enforce BLACK_EXTENSIONS.
        $uploadConfig['upload_ext'] = $uploadConfig['upload_ext'] ? explode(',', $uploadConfig['upload_ext']) : [
            // image
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif',
            // document
            'pdf', 'txt', 'md', 'csv',
            'doc', 'docx',
            'xls', 'xlsx',
            'ppt', 'pptx',
            // video
            'mp4', 'mov', 'm4v', 'webm', 'mkv', 'avi',
        ];
        $uploadConfig['upload_size'] = $uploadConfig['upload_size'] ?: 5;
        return $uploadConfig;
    }

    public static function generatePath(string $filename, ?string $mime = null, ?string $prefix = null): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $basename = bin2hex(random_bytes(10));

        if ($mime && !$extension) {
            $detector = new \League\MimeTypeDetection\ExtensionMimeTypeDetector();
            $extension = $detector->lookupExtension($mime);
        }

        $filename = $extension ? sprintf('%s.%s', $basename, $extension) : $basename;

        $pathParts = array_filter([
            $prefix ?: '',
            date('Y/m/d'),
            $filename
        ]);

        return [
            'path' => implode('/', $pathParts),
            'name' => $filename,
            'ext' => $extension,
            'mime' => $mime,
        ];
    }

    public static function generatePathContent($file, string $filename, ?string $mime = null, ?string $prefix = null): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_check_');
        file_put_contents($tempFile, stream_get_contents($file, 8192));

        try {
            $mime = $mime ?: mime_content_type($tempFile);
            return self::generatePath($filename, $mime, $prefix);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public static function validateFile(string $extension, ?int $size = 0): void
    {
        $config = self::getUploadConfig();

        if (in_array(strtolower($extension), self::BLACK_EXTENSIONS)) {
            throw new ExceptionBusiness('系统禁止上传该文件类型，存在安全风险: .' . $extension);
        }

        if ($size && $size > $config['upload_size'] * 1024 * 1024) {
            throw new ExceptionBusiness('文件大小超过限制 (' . ($config['upload_size'] / 1024 / 1024) . 'MB)');
        }

        if ($extension && $config['upload_ext'] && !in_array(strtolower($extension), array_map('strtolower', $config['upload_ext']))) {
            throw new ExceptionBusiness('不支持的文件扩展名: ' . $extension);
        }
    }

}
