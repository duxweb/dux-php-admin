<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\App;
use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class SystemTranslation extends \Core\Database\Model
{
    public $table = 'system_translation';

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->string('key')->comment('语言变量');
        $table->json('data')->comment('翻译');
        $table->timestamps();
    }

    protected $casts = ['data' => 'array'];

    protected static function boot()
    {
        parent::boot();
        static::created(function () {
            App::di()->set('system.lang', null);
        });
        static::updated(function () {
            App::di()->set('system.lang', null);
        });
        static::deleted(function () {
            App::di()->set('system.lang', null);
        });
    }

    /**
     * 获取翻译
     * @param string $code
     * @return array
     */
    public static function translations(string $code): array
    {
        if (App::di()->has('system.lang')) {
            return App::di()->get('system.lang');
        }
        $data = self::query()->get()->reduce(function ($carry, $item) use ($code) {
            $carry[$item->key] = $item->data[$code] ?? '';
            return $carry;
        }, []);
        App::di()->set('system.lang', $data);
        return $data;
    }
}
