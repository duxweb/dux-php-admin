## 模块简介

`Install` 是项目的安装与应用商店模块，负责首次安装向导、安装前拦截、运行时配置写入、数据库初始化、菜单同步，以及云端模块安装/升级/卸载流程。

这个模块既是首次交付入口，也是后续“从应用商店安装模块”的核心实现。

## 能力状态约定

- `稳定可用`：已经在当前安装流程或商店流程中实际承担核心职责
- `扩展入口`：推荐其他模块、前端或交付流程对接的入口
- `接入前验证`：代码已开放，但因为牵涉安装、升级、Composer、数据库等高风险动作，正式集成前应先在测试环境验证

## 核心特点

- 在系统未安装时，拦截绝大多数请求并强制跳转到安装向导
- 提供多步骤安装流程：许可、环境检查、系统信息、数据库、模块选择、执行安装
- 可写入 `use` 和 `database` 配置，并在运行时重载配置和数据库连接
- 支持 SQLite、MySQL、PostgreSQL 的安装前校验与数据库创建
- 提供安装日志和应用商店动作日志的流式输出适配能力
- 提供云端模块列表、详情、安装、升级、卸载和任务互斥锁

## 运行时行为

### 未安装时

- `App\Install\App::init()` 会给 `web` 应用加一层中间件
- 除 `/install` 开头的请求外，都会被重定向到 `/install/license`
- `App\Install\App::register()` 会把根路径 `/` 重定向到安装页

### 已安装时

- `Install` 模块不再接管安装流程
- 如果系统当前没有首页路由，`App\Install\App::boot()` 会自动注册一个欢迎页 `/`

## 对外开放的数据边界

```text
Install 模块本身没有额外沉淀出供其他模块稳定依赖的业务模型
它的主要集成边界是 Service 与日志输出适配器
```

## 对外开放的 Service

### InstallService

状态：`稳定可用`、`扩展入口`

```php
// App\Install\Service\InstallService

// 判断是否已经安装，依据 data/install.lock
isInstalled(): bool
// 校验系统信息与数据库信息，写入配置并生成待执行 token
prepare(array $payload): string
// 读取和删除安装待执行 token
getPendingToken(string $token): array
deletePendingToken(string $token): void
// 安装流程互斥锁，避免并发安装
acquireRunningLock()
releaseRunningLock($handle): void
// 写入安装锁文件，并清理路由缓存
markInstalled(): void
// 读取云端模块列表，并安装安装步骤中选中的模块
fetchCloudModules(?string $cloudKey = null, ?string $cloudServer = null): array
installCloudModules(array $packages, ?string $cloudKey = null, bool $upgradeInstalled = false, array $installedPackages = [], ?string $cloudServer = null): array
// 执行安装阶段的 Composer 安装、数据库迁移、菜单同步
runComposerInstall(OutputInterface $output): void
syncDatabase(OutputInterface $output): void
syncMenus(OutputInterface $output): void
```

这个 Service 对“首次安装”负责，重点是配置写入、环境准备和安装流水线。

### CloudModuleService

状态：`稳定可用`、`扩展入口`、`接入前验证`

```php
// App\Install\Service\CloudModuleService

// 从云端读取可安装模块列表，并补充本地安装状态、版本和服务器延迟
listModules(?string $cloudKey = null, ?string $cloudServer = null): array
// 读取某个模块的详细信息，identifier 可以是商店 ID 或模块 app 名
moduleDetail(string $identifier, ?string $cloudKey = null, ?string $cloudServer = null): array
// 直接安装一组模块包，适合安装向导“选择模块”这一步
installModules(array $packages, ?string $cloudKey = null, bool $upgradeInstalled = false, array $installedPackages = [], ?string $cloudServer = null): array
// 为安装、升级、卸载动作生成一个待执行 token
prepareStoreAction(array $payload, ?string $cloudKey = null, ?string $cloudServer = null): string
// 读取 token 并执行对应的商店动作
runStoreActionToken(string $token, OutputInterface $output): array
// 执行模块安装、升级、卸载主流程，并按选项决定是否执行 Composer、菜单同步、数据库同步
installOrUpgrade(string $app, string $action, array $options, OutputInterface $output, ?string $cloudKey = null, ?string $cloudServer = null): array
// 商店任务互斥锁，以及 token 的读取和清理
acquireStoreRunningLock()
releaseStoreRunningLock($handle): void
getStoreToken(string $token): array
deleteStoreToken(string $token): void
```

这个 Service 对“安装后继续从应用商店管理模块”负责，重点是模块商店列表、详情和操作流。

因为它会联动云端、Composer、数据库迁移和菜单同步，正式对开发者开放前建议在测试环境完整验证一次安装、升级、卸载链路。

### SseCommandOutput

状态：`稳定可用`、`扩展入口`

```php
// App\Install\Service\SseCommandOutput

// 注册一条日志输出后的回调，通常用于把日志推送到 SSE 流
__construct(callable $logger)
// 强制把缓冲中的日志立即推送出去
flush(): void
```

这是一个面向 SSE 场景的输出适配器，供安装流程和商店动作把实时日志推送到前端。

## 安装步骤说明

### 环境检查

```text
PHP 版本
# 当前要求 >= 8.4

扩展
# pdo、openssl、mbstring、json、fileinfo、curl

可选扩展
# redis、imagick

函数
# proc_open、proc_get_status、proc_close

可选函数
# proc_terminate、exec

目录写权限
# config/、data/、app/
```

### 执行阶段

```bash
composer install
db:sync
menu:sync

# 写入安装锁并返回后台与首页链接
```

## 模块事件

```text
当前 Install 模块没有定义额外的模块级 Event 类
当前也没有面向其他模块的事件订阅协议
```

如果要和它集成，主要入口是：

- `InstallService`
- `CloudModuleService`
- `SseCommandOutput`

## 其他模块接入建议

- 如果模块需要参与“首次安装后的菜单落库”，请在自身模块完成后使用 `menu:sync`，不要直接操作 `system_menu`。
- 如果模块要接入应用商店，请优先对接 `CloudModuleService`，不要自行复制云端列表和安装流程。
- 如果需要把安装日志或模块安装日志转成实时输出，优先复用 `SseCommandOutput`。
- 如果你的模块会在未安装阶段被访问，要注意 `Install` 中间件会把请求统一重定向到安装页。
