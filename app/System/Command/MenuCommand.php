<?php

declare(strict_types=1);

namespace App\System\Command;

use App\System\Data\CombinedMenuInterface;
use App\System\Data\JsonMenuInterface;
use App\System\Service\Menu;
use Core\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Core\Command\Attribute\Command]
class MenuCommand extends Command
{
    protected function configure(): void
    {
        $this->setName("menu:sync")
            ->setDescription('Sync menu to database')
            ->addArgument('module', InputArgument::OPTIONAL, 'Module name to sync (optional)')
            ->addArgument('app', InputArgument::OPTIONAL, 'App type to sync (optional, sync all if not specified)');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting menu sync...</info>');

        $db = App::db()->getConnection();
        $appConfig = App::config('app');
        $targetModule = $input->getArgument('module');
        $targetApp = $input->getArgument('app');

        if (!$targetApp) {
            return $this->syncAllAppTypes($db, $appConfig, $targetModule, $output);
        }

        return $this->syncSpecificApp($db, $appConfig, $targetModule, $targetApp, $output);
    }

    private function syncAllAppTypes($db, $appConfig, $targetModule, $output): int
    {
        $output->writeln('<info>Syncing all app types...</info>');

        $allAppTypes = $this->getAllAppTypes($appConfig, $targetModule);
        $totalSynced = 0;

        foreach ($allAppTypes as $appType => $modules) {
            if (empty($modules)) continue;

            $output->writeln("<info>Processing app type: $appType</info>");
            if ($this->syncAppType($db, $appType, $modules, $output)) {
                $totalSynced++;
            }
        }

        $message = $totalSynced > 0 
            ? "<info>Successfully synced $totalSynced app types</info>"
            : '<comment>No menus were synced</comment>';
        $output->writeln($message);

        return Command::SUCCESS;
    }

    private function syncSpecificApp($db, $appConfig, $targetModule, $targetApp, $output): int
    {
        $menuKey = $targetApp . 'Menu';
        $output->writeln("<info>Syncing menus for app: $targetApp</info>");

        $menuData = $this->collectMenuData($appConfig, $targetModule, $menuKey, $output);
        
        if (!empty($menuData['menus'])) {
            $sortedMenus = $this->sortMenusByHierarchy($menuData['menus']);
            Menu::install($db, new CombinedMenuInterface([$sortedMenus]), $targetApp);
            $output->writeln("<info>Successfully synced menus to $targetApp application</info>");
        }

        $message = empty($menuData['modules']) 
            ? ($targetModule ? "No $menuKey found for module: $targetModule" : "No $menuKey found")
            : 'Menu sync completed for modules: ' . implode(', ', $menuData['modules']);
        $output->writeln($message);

        return Command::SUCCESS;
    }

    private function getAllAppTypes($appConfig, $targetModule): array
    {
        $appTypes = [];

        foreach ($appConfig->get('registers') as $appClass) {
            $moduleName = $this->getModuleName($appClass);
            
            if ($targetModule && strtolower($targetModule) !== strtolower($moduleName)) {
                continue;
            }

            $appJson = $this->loadAppJson($appClass);
            if (!$appJson) continue;

            foreach ($appJson as $key => $value) {
                if (str_ends_with($key, 'Menu') && is_array($value)) {
                    $appType = substr($key, 0, -4);
                    $appTypes[$appType][] = $moduleName;
                }
            }
        }

        return $appTypes;
    }

    private function syncAppType($db, $appType, $modules, $output): bool
    {
        $menuKey = $appType . 'Menu';
        $allMenus = [];
        $syncedModules = [];

        foreach ($modules as $moduleName) {
            $appClass = $this->getAppClassByModule($moduleName);
            if (!$appClass) continue;

            $appJson = $this->loadAppJson($appClass);
            if ($appJson && isset($appJson[$menuKey]) && is_array($appJson[$menuKey])) {
                $menuInterface = new JsonMenuInterface($appJson[$menuKey]);
                $allMenus = array_merge($allMenus, $menuInterface->getData());
                $syncedModules[] = $moduleName;
                $output->writeln("<info>Collected $menuKey for $moduleName</info>");
            }
        }

        if (!empty($allMenus)) {
            $sortedMenus = $this->sortMenusByHierarchy($allMenus);
            Menu::install($db, new CombinedMenuInterface([$sortedMenus]), $appType);
            $output->writeln("<info>Successfully synced $menuKey to $appType application</info>");
            return true;
        }

        return false;
    }

    private function collectMenuData($appConfig, $targetModule, $menuKey, $output): array
    {
        $allMenus = [];
        $syncedModules = [];

        foreach ($appConfig->get('registers') as $appClass) {
            $moduleName = $this->getModuleName($appClass);

            if ($targetModule && strtolower($targetModule) !== strtolower($moduleName)) {
                continue;
            }

            $appJson = $this->loadAppJson($appClass);
            if (!$appJson || !isset($appJson[$menuKey]) || !is_array($appJson[$menuKey])) {
                continue;
            }

            $menuInterface = new JsonMenuInterface($appJson[$menuKey]);
            $allMenus = array_merge($allMenus, $menuInterface->getData());
            $syncedModules[] = $moduleName;
            $output->writeln("<info>Collected menu for $moduleName</info>");
        }

        return ['menus' => $allMenus, 'modules' => $syncedModules];
    }

    private function loadAppJson(string $appClass): ?array
    {
        $appJsonPath = $this->getAppPath($appClass) . '/app.json';
        if (!file_exists($appJsonPath)) {
            return null;
        }

        $content = file_get_contents($appJsonPath);
        return $content ? json_decode($content, true) : null;
    }

    private function getAppClassByModule(string $moduleName): ?string
    {
        $appConfig = App::config('app');
        foreach ($appConfig->get('registers') as $appClass) {
            if (strtolower($this->getModuleName($appClass)) === strtolower($moduleName)) {
                return $appClass;
            }
        }
        return null;
    }

    private function getAppPath(string $appClass): string
    {
        $classPath = str_replace('\\', '/', $appClass);
        $parts = explode('/', $classPath);
        $parts = array_slice($parts, 1, -1);
        return app_path(implode('/', $parts));
    }

    private function getModuleName(string $appClass): string
    {
        $parts = explode('\\', $appClass);
        return strtolower($parts[count($parts) - 2]);
    }

    private function sortMenusByHierarchy(array $menus): array
    {
        usort($menus, fn($a, $b) => ($a['sort'] ?? 999) <=> ($b['sort'] ?? 999));

        $sorted = [];
        $menuMap = array_column($menus, null, 'name');

        $addMenuAndChildren = function ($menuName, $addedMenus = []) use (&$sorted, &$menuMap, &$addMenuAndChildren) {
            if (in_array($menuName, $addedMenus) || !isset($menuMap[$menuName])) {
                return $addedMenus;
            }

            $menu = $menuMap[$menuName];

            if (!empty($menu['parent']) && !in_array($menu['parent'], $addedMenus)) {
                $addedMenus = $addMenuAndChildren($menu['parent'], $addedMenus);
            }

            if (!in_array($menuName, $addedMenus)) {
                $sorted[] = $menu;
                $addedMenus[] = $menuName;
            }

            return $addedMenus;
        };

        $addedMenus = [];
        foreach ($menus as $menu) {
            if (!in_array($menu['name'], $addedMenus)) {
                $addedMenus = $addMenuAndChildren($menu['name'], $addedMenus);
            }
        }

        return $sorted;
    }
}