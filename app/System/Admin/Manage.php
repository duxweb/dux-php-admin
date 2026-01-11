<?php

namespace App\System\Admin;

use App\System\Handler\ManageHandler;
use App\System\Models\SystemMenu;
use App\System\Models\SystemUser;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '', name: 'system.manage', actions: false)]
class Manage extends ManageHandler
{
    protected string $app = 'admin';

    #[Action(methods: 'GET', route: '/router')]
    public function router(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $id = (int)$auth['id'];

        $menu = SystemMenu::getMenu($this->app);

        $userInfo = SystemUser::query()->with(['role'])->find($id);
        $permission = $userInfo->permission ?? [];

        if ($permission) {
            $menu = $this->filterMenuByPermission($menu, $permission);
        }

        return send($response, "ok", $menu);
    }

    private function filterMenuByPermission(array $menu, array $permissions): array
    {
        // 第一步：过滤出有权限的菜单项和所有目录
        $filtered = array_filter($menu, function ($item) use ($permissions) {
            // 目录（没有 path）保留
            if (empty($item['path'])) {
                return true;
            }
            // 菜单项需要检查权限
            return in_array($item['name'], $permissions, true);
        });

        // 第二步：递归删除空目录
        $hasChanges = true;
        while ($hasChanges) {
            $hasChanges = false;
            
            // 统计每个父级的子项数量
            $childrenCount = [];
            foreach ($filtered as $item) {
                if (!empty($item['parent'])) {
                    $childrenCount[$item['parent']] = ($childrenCount[$item['parent']] ?? 0) + 1;
                }
            }
            
            // 过滤掉没有子项的目录
            $newFiltered = [];
            foreach ($filtered as $item) {
                // 如果是目录且没有子项，跳过
                if (empty($item['path']) && (!isset($childrenCount[$item['name']]) || $childrenCount[$item['name']] === 0)) {
                    $hasChanges = true;
                    continue;
                }
                $newFiltered[] = $item;
            }
            
            $filtered = $newFiltered;
        }

        return array_values($filtered);
    }
}
