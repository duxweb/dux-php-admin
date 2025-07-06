<?php

declare(strict_types=1);

namespace App\System\Data;

use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class Menu implements MenuInterface
{

    public function getData(): array
    {
        return [
            $this->index(),
            $this->system(),
            $this->docs(),
            $this->systemUser(),
            $this->systemConfig(),
            $this->systemLocale(),
            $this->systemLog(),
        ];
    }

    private function index(): array
    {
        return [
            [
                'parent' => null,
                'type' => 'menu',
                'label' => '首页',
                'name' => 'system.index',
                'label_lang' => 'system.index',
                'path' => 'system/index',
                'icon' => 'i-tabler:dashboard',
                'loader' => 'System/Home/index',
                'hidden' => null,
                'sort' => 0,
            ],
        ];
    }

    private function docs(): array
    {
        return [
            [
                'parent' => null,
                'type' => 'menu',
                'label' => 'API文档',
                'name' => 'system.docs',
                'label_lang' => 'system.docs',
                'path' => 'system/docs',
                'icon' => 'i-tabler:book',
                'loader' => 'System/Docs/index',
                'hidden' => null,
                'sort' => 1000,
            ],
        ];
    }

    private function system(): array
    {
        return [
            [
                'parent' => null,
                'type' => 'directory',
                'label' => '系统',
                'name' => 'system',
                'label_lang' => null,
                'path' => null,
                'icon' => 'i-tabler:adjustments-cog',
                'loader' => null,
                'hidden' => null,
                'sort' => 9999,
            ],
            [
                'parent' => 'system',
                'type' => 'directory',
                'label' => '管理员管理',
                'name' => 'system.userGroup',
                'label_lang' => 'system.userGroup',
                'path' => null,
                'icon' => null,
                'loader' => null,
                'hidden' => null,
            ],
            [
                'parent' => 'system',
                'type' => 'directory',
                'label' => '系统配置',
                'name' => 'system.config',
                'label_lang' => 'system.config',
                'path' => null,
                'icon' => null,
                'loader' => null,
                'hidden' => null,
            ],
            [
                'parent' => 'system',
                'type' => 'directory',
                'label' => '语言管理',
                'name' => 'system.localeGroup',
                'label_lang' => 'system.localeGroup',
                'path' => null,
                'icon' => null,
                'loader' => null,
                'hidden' => null,
            ],
            [
                'parent' => 'system',
                'type' => 'directory',
                'label' => '系统日志',
                'name' => 'system.log',
                'label_lang' => 'system.log',
                'path' => null,
                'icon' => null,
                'loader' => null,
                'hidden' => null,
            ],
            [
                'parent' => 'system',
                'type' => 'directory',
                'label' => '系统管理',
                'name' => 'system.manage',
                'label_lang' => 'system.manage',
                'path' => null,
                'icon' => null,
                'loader' => null,
                'hidden' => null,
            ],
            [
                'parent' => 'system',
                'type' => 'menu',
                'label' => '个人信息',
                'name' => 'system.profile',
                'label_lang' => 'system.profile',
                'path' => 'system/profile',
                'icon' => null,
                'loader' => 'System/Profile/index',
                'hidden' => true,
            ],
        ];
    }

    private function systemUser(): array
    {
        return [
            [
                'parent' => 'system.userGroup',
                'type' => 'menu',
                'label' => '部门管理',
                'name' => 'system.dept.list',
                'label_lang' => 'system.dept.list',
                'path' => 'system/dept',
                'icon' => null,
                'loader' => 'System/Dept/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.userGroup',
                'type' => 'menu',
                'label' => '角色管理',
                'name' => 'system.role.list',
                'label_lang' => 'system.role.list',
                'path' => 'system/role',
                'icon' => null,
                'loader' => 'System/Role/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.role.list',
                'type' => 'menu',
                'label' => '新增角色',
                'name' => 'system.role.create',
                'label_lang' => 'system.role.create',
                'path' => 'system/role/create',
                'icon' => null,
                'loader' => 'System/Role/form',
                'hidden' => true,
            ],
            [
                'parent' => 'system.role.list',
                'type' => 'menu',
                'label' => '编辑角色',
                'name' => 'system.role.edit',
                'label_lang' => 'system.role.edit',
                'path' => 'system/role/edit/:id',
                'icon' => null,
                'loader' => 'System/Role/form',
                'hidden' => true,
            ],
            [
                'parent' => 'system.userGroup',
                'type' => 'menu',
                'label' => '用户管理',
                'name' => 'system.user.list',
                'label_lang' => 'system.user.list',
                'path' => 'system/user',
                'icon' => null,
                'loader' => 'System/User/table',
                'hidden' => null,
            ],
        ];
    }

    private function systemConfig(): array
    {
        return [
            [
                'parent' => 'system.config',
                'type' => 'menu',
                'label' => '接口授权',
                'name' => 'system.api.list',
                'label_lang' => 'system.api.list',
                'path' => 'system/api',
                'icon' => null,
                'loader' => 'System/Api/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.config',
                'type' => 'menu',
                'label' => '菜单配置',
                'name' => 'system.menu.list',
                'label_lang' => 'system.menu.list',
                'path' => 'system/menu',
                'icon' => null,
                'loader' => 'System/Menu/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.config',
                'type' => 'menu',
                'label' => '配置参数',
                'name' => 'system.setting.list',
                'label_lang' => 'system.setting.list',
                'path' => 'system/setting',
                'icon' => null,
                'loader' => 'System/Setting/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.config',
                'type' => 'menu',
                'label' => '数据字典',
                'name' => 'system.dictionary.list',
                'label_lang' => 'system.dictionary.list',
                'path' => 'system/dictionary',
                'icon' => null,
                'loader' => 'System/Dictionary/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.config',
                'type' => 'menu',
                'label' => '存储配置',
                'name' => 'system.storage.list',
                'label_lang' => 'system.storage.list',
                'path' => 'system/storage',
                'icon' => null,
                'loader' => 'System/Storage/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.config',
                'type' => 'menu',
                'label' => '系统设置',
                'name' => 'system.config.info',
                'label_lang' => 'system.config.info',
                'path' => 'system/config',
                'icon' => null,
                'loader' => 'System/Config/form',
                'hidden' => null,
            ],
        ];
    }

    private function systemLocale(): array
    {
        return [
            [
                'parent' => 'system.localeGroup',
                'type' => 'menu',
                'label' => '语言列表',
                'name' => 'system.locale.list',
                'label_lang' => 'system.locale.list',
                'path' => 'system/locale',
                'icon' => null,
                'loader' => 'System/Locale/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.localeGroup',
                'type' => 'menu',
                'label' => '语言数据',
                'name' => 'system.localeData.list',
                'label_lang' => 'system.localeData.list',
                'path' => 'system/localeData',
                'icon' => null,
                'loader' => 'System/LocaleData/table',
                'hidden' => null,
            ],
        ];
    }

    private function systemLog(): array
    {
        return [
            [
                'parent' => 'system.log',
                'type' => 'menu',
                'label' => '操作日志',
                'name' => 'system.operate.list',
                'label_lang' => 'system.operate.list',
                'path' => 'system/log',
                'icon' => null,
                'loader' => 'System/Operate/table',
                'hidden' => null,
            ],
            [
                'parent' => 'system.log',
                'type' => 'menu',
                'label' => '登录日志',
                'name' => 'system.login.list',
                'label_lang' => 'system.login.list',
                'path' => 'system/login',
                'icon' => null,
                'loader' => 'System/Login/table',
                'hidden' => null,
            ],
        ];
    }
}
