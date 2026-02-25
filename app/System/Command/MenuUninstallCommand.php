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
class MenuUninstallCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('menu:uninstall')
            ->setDescription('Uninstall menu from database')
            ->addArgument('module', InputArgument::OPTIONAL, 'Module name to uninstall (optional)')
            ->addArgument('app', InputArgument::OPTIONAL, 'App type to uninstall (optional, uninstall all if not specified)');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting menu uninstall...</info>');

        $db = App::db()->getConnection();
        $appConfig = App::config('app');
        $targetModule = (string)$input->getArgument('module');
        $targetApp = (string)$input->getArgument('app');

        if (!$targetApp) {
            return $this->uninstallAllAppTypes($db, $appConfig, $targetModule, $output);
        }

        return $this->uninstallSpecificApp($db, $appConfig, $targetModule, $targetApp, $output);
    }

    private function uninstallAllAppTypes($db, $appConfig, string $targetModule, OutputInterface $output): int
    {
        $output->writeln('<info>Uninstalling all app type menus...</info>');

        $allAppTypes = $this->getAllAppTypes($appConfig, $targetModule);
        $totalDeleted = 0;

        foreach ($allAppTypes as $appType => $modules) {
            if (empty($modules)) {
                continue;
            }

            $output->writeln("<info>Processing app type: {$appType}</info>");
            $deleted = $this->uninstallAppType($db, $appType, $modules, $output);
            $totalDeleted += $deleted;
        }

        if ($totalDeleted > 0) {
            $output->writeln("<info>Successfully uninstalled {$totalDeleted} menus</info>");
        } else {
            $output->writeln('<comment>No menus were uninstalled</comment>');
        }

        return Command::SUCCESS;
    }

    private function uninstallSpecificApp($db, $appConfig, string $targetModule, string $targetApp, OutputInterface $output): int
    {
        $menuKey = $targetApp . 'Menu';
        $output->writeln("<info>Uninstalling menus for app: {$targetApp}</info>");

        $menuData = $this->collectMenuData($appConfig, $targetModule, $menuKey, $output);
        if (!empty($menuData['menus'])) {
            $deleted = Menu::uninstall($db, new CombinedMenuInterface($menuData['menus']), $targetApp);
            $output->writeln("<info>Successfully uninstalled {$deleted} menus from {$targetApp}</info>");
        }

        if (empty($menuData['modules'])) {
            $output->writeln($targetModule ? "No {$menuKey} found for module: {$targetModule}" : "No {$menuKey} found");
        } else {
            $output->writeln('Menu uninstall completed for modules: ' . implode(', ', $menuData['modules']));
        }

        return Command::SUCCESS;
    }

    private function uninstallAppType($db, string $appType, array $modules, OutputInterface $output): int
    {
        $menuKey = $appType . 'Menu';
        $allMenus = [];

        foreach ($modules as $moduleName) {
            $appClass = $this->getAppClassByModule($moduleName);
            if (!$appClass) {
                continue;
            }

            $appJson = $this->loadAppJson($appClass);
            if ($appJson && isset($appJson[$menuKey]) && is_array($appJson[$menuKey])) {
                $menuInterface = new JsonMenuInterface($appJson[$menuKey]);
                $allMenus = array_merge($allMenus, $menuInterface->getData());
                $output->writeln("<info>Collected {$menuKey} for {$moduleName}</info>");
            }
        }

        if (empty($allMenus)) {
            return 0;
        }

        $deleted = Menu::uninstall($db, new CombinedMenuInterface($allMenus), $appType);
        $output->writeln("<info>Uninstalled {$deleted} menus for {$appType} ({$menuKey})</info>");

        return $deleted;
    }

    private function getAllAppTypes($appConfig, string $targetModule): array
    {
        $appTypes = [];

        foreach ($appConfig->get('registers') as $appClass) {
            $moduleName = $this->getModuleName($appClass);
            if ($targetModule && strtolower($targetModule) !== strtolower($moduleName)) {
                continue;
            }

            $appJson = $this->loadAppJson($appClass);
            if (!$appJson) {
                continue;
            }

            foreach ($appJson as $key => $value) {
                if (str_ends_with($key, 'Menu') && is_array($value)) {
                    $appType = substr($key, 0, -4);
                    $appTypes[$appType][] = $moduleName;
                }
            }
        }

        return $appTypes;
    }

    private function collectMenuData($appConfig, string $targetModule, string $menuKey, OutputInterface $output): array
    {
        $allMenus = [];
        $modules = [];

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
            $modules[] = $moduleName;
            $output->writeln("<info>Collected menu for {$moduleName}</info>");
        }

        return [
            'menus' => $allMenus,
            'modules' => $modules,
        ];
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
}
