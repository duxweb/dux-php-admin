<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Data\Data\Menu;
use App\System\Service\Menu as ServiceMenu;
use Core\Database\Attribute\AutoMigrate;
use Illuminate\Database\Connection;

#[AutoMigrate]
class DataConfig extends \Core\Database\Model
{
    public $table = 'data_config';

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->string('name')->comment('数据名称')->nullable();
        $table->string('label')->comment('数据标签')->index()->nullable();
        $table->json('table_data')->comment('表格配置')->nullable();
        $table->json('form_data')->comment('表单配置')->nullable();
        $table->string('table_type', 10)->comment('列表类型 list列表 pages分页 tree树形')->nullable()->default('pages');
        $table->string('form_type', 10)->comment('表单类型 modal弹窗 drawer抽屉 page页面')->nullable()->default('modal');
        $table->boolean('api_sign')->comment('Api 鉴权')->default(true);
        $table->boolean('api_user')->comment('用户授权')->default(true);
        $table->boolean('api_user_self')->comment('自身数据')->default(true);
        $table->boolean('api_list')->comment('列表权限')->default(true);
        $table->boolean('api_info')->comment('详情权限')->default(true);
        $table->boolean('api_create')->comment('创建权限')->default(true);
        $table->boolean('api_update')->comment('更新权限')->default(true);
        $table->boolean('api_delete')->comment('删除权限')->default(true);
        $table->timestamps();
    }

    public function install(Connection $db)
    {
        ServiceMenu::install($db, new Menu(), 'admin');
    }

    protected $casts = [
        'table_data' => 'array',
        'form_data' => 'array',
        'api_sign' => 'boolean',
        'api_user' => 'boolean',
        'api_user_self' => 'boolean',
        'api_list' => 'boolean',
        'api_info' => 'boolean',
        'api_create' => 'boolean',
        'api_update' => 'boolean',
        'api_delete' => 'boolean',
    ];
}
