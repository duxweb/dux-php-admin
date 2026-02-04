<?php

namespace App\System\Service;

use App\System\Models\SystemStorage;
use Core\Handlers\ExceptionBusiness;
use Core\Storage\Contracts\StorageInterface;
use Core\Storage\Storage as StorageStorage;

class Storage
{
  public static function getObject(string|int|null $name = null): StorageInterface
  {
    if (is_int($name)) {
      $info = SystemStorage::query()->find($name);
    } elseif (is_string($name) && $name !== '') {
      $info = SystemStorage::query()->where('name', $name)->first();
    } else {
      $info = SystemStorage::query()->where('id', Config::getValue('system.storage'))->first();
    }
    if (!$info) {
      throw new ExceptionBusiness('Storage not found');
    }

    $config = $info->config;
    if ($info->type === 'local') {
      $config['root'] = public_path();
    }

    return (new StorageStorage($info->type, $config, function ($path) {
      return self::localSign($path);
    }))->getInstance();
  }

  public static function localSign(string $path): string
  {
    $data = [
      'path' => $path,
      'expire' => time() + 3600
    ];
    return encryption(json_encode($data));
  }

  public static function localVerify(string $path, string $sign): bool
  {
    try {
      $content = decryption($sign);
      $data = json_decode($content, true);

      if ($data['path'] !== $path) {
        return false;
      }

      if ($data['expire'] < time()) {
        return false;
      }

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
}
