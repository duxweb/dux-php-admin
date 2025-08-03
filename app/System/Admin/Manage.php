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
        $filtered = array_filter($menu, function ($item) use ($permissions) {
            if (empty($item['name'])) {
                return true;
            }
            return in_array($item['name'], $permissions, true);
        });

        $validNames = [];
        foreach ($filtered as $item) {
            if (!empty($item['name'])) {
                $validNames[] = $item['name'];
            }
        }

        $result = array_filter($filtered, function ($item) use ($validNames) {
            if (empty($item['parent'])) {
                return true;
            }
            return in_array($item['parent'], $validNames, true);
        });

        return array_values($result);
    }
}
