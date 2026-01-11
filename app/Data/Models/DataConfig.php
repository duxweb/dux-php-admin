<?php

declare(strict_types=1);

namespace App\Data\Models;

use Core\Database\Attribute\AutoMigrate;

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
        $table->json('field_data')->comment('字段配置')->nullable();
        $table->string('table_type', 10)->comment('列表类型 list列表 pages分页 tree树形')->nullable()->default('pages');
        $table->string('form_type', 10)->comment('表单类型 modal弹窗 drawer抽屉 page页面')->nullable()->default('modal');
        // 列表默认排序（基于 id）
        $table->string('id_sort', 5)->comment('ID 默认排序 asc/desc')->nullable()->default('asc');
        // 新增：提交策略相关配置
        $table->boolean('post_retry')->comment('是否去重')->default(false);
        $table->integer('post_limit')->comment('限流(窗口内X条)')->default(0);
        $table->integer('post_window')->comment('限流窗口(分钟)')->default(1);
        $table->tinyInteger('post_tactics')->comment('限流策略 0整体 1按IP 2按用户')->default(0);
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

    protected $casts = [
        'table_data' => 'array',
        'form_data' => 'array',
        'field_data' => 'array',
        'id_sort' => 'string',
        'post_retry' => 'boolean',
        'post_limit' => 'int',
        'post_window' => 'int',
        'post_tactics' => 'int',
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
