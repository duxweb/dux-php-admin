## 模块简介

`System` 是整个 Dux 后台的基础系统模块，负责初始化管理端能力与系统级运行支撑，并提供权限、菜单、配置、上传、存储、通知、公告、计划任务、队列监控等通用能力。绝大多数业务模块都会直接或间接依赖它。

对其他模块来说，`System` 既是基础设施模块，也是最主要的扩展入口模块：

- 通过事件扩展管理端壳层
- 通过 `Service` 读取配置、发送通知、接入存储
- 通过菜单同步命令把 `app.json` 中的菜单注册到后台
- 通过事件与 Service 向其他模块开放系统级基础能力

## 能力状态约定

- `稳定可用`：已经在当前项目主流程里长期使用
- `扩展入口`：推荐其他模块直接依赖的正式接入点
- `预留能力`：代码存在但更偏内部、示例或未来扩展，接入前建议自行验证

## 核心特点

- 初始化 `admin` 资源应用，统一挂载登录、权限和操作日志中间件
- 提供后台管理壳层 `/manage/`，并允许其他模块通过事件注入新的管理端配置
- 提供用户、角色、部门、菜单、字典、语言包、地区、存储、上传、系统设置等基础系统能力
- 提供通知、公告、备忘录、统计、定时任务、队列监控等通用功能
- 提供菜单同步命令，供模块把 `app.json` 里的菜单声明写入数据库

## 模块边界

- 适合放在 `System` 的能力：后台基础设施、公共配置、权限与身份、公共上传与存储、面向全站的系统级消息
- 不适合放在 `System` 的能力：业务模块自己的领域规则、业务内容模型、业务专用工作流

## 对外开放的数据模型

```php
// 系统配置键值表
App\System\Models\Config

// 后台菜单树
App\System\Models\SystemMenu

// 存储驱动配置
App\System\Models\SystemStorage

// 站内通知
App\System\Models\SystemNotice

// 系统公告与已读记录
App\System\Models\SystemBulletin
App\System\Models\SystemBulletinRead

// 管理员、角色、部门
App\System\Models\SystemUser
App\System\Models\SystemRole
App\System\Models\SystemDept

// 定时任务配置
App\System\Models\SystemSchedulerTask
```

这些模型适合在跨模块协作时作为稳定数据边界使用。管理端页面与路由本身不建议作为模块间契约。

## 对外开放的 Service

### Config

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\Config

// 读取 JSON 配置并转为数组，适合 system、theme_xxx 这类结构化配置
getJsonValue(string $name, mixed $default = null)
// 读取配置值，支持点路径，例如 system.storage
getValue(string $name, mixed $default = null): mixed
// 写入配置，数组会自动转 JSON，并清空静态缓存
setValue(string $name, mixed $value): void
```

这是其他模块最常用的系统级接口。模块自己的设置页如果只是键值保存，优先复用它。

### Menu

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\Menu

// 把菜单定义写入 system_menu，自动补 ID、修复树并清缓存
install(Connection $db, MenuInterface $menu, string $app, ?int $lastId = null): array
// 按菜单定义从 system_menu 中卸载菜单
uninstall(Connection $db, MenuInterface $menu, string $app): int
// 为一组菜单声明分配数据库 ID、父子关系和通用字段
assignMultipleMenuIds(string $app, array $menuGroups, int $startId = 1): array
```

配套的数据接口：

- `App\System\Data\MenuInterface`：菜单提供方接口，只要求实现 `getData(): array`
- `App\System\Data\JsonMenuInterface`：把 `app.json` 的树结构菜单转换成扁平菜单数组
- `App\System\Data\CombinedMenuInterface`：把多组菜单合并后作为统一输入

如果模块需要把后台菜单注册到系统，推荐流程是：在 `app.json` 里写 `adminMenu`，再调用 `php dux menu:sync <module> admin`。

### Storage

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\Storage

// 按存储 ID、名称或系统默认存储返回 Flysystem 适配实例
getObject(string|int|null $name = null): StorageInterface
// 给本地存储路径生成临时签名
localSign(string $path): string
// 校验本地存储签名
localVerify(string $path, string $sign): bool
```

当业务模块需要直接读写系统配置过的存储驱动时，优先使用这个 Service，而不是自行拼接存储配置。

### Upload

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\Upload

// 获取系统上传配置，自动合并默认扩展名和大小限制
getUploadConfig(): array
// 按日期生成存储路径和文件名
generatePath(string $filename, ?string $mime = null, ?string $prefix = null): array
// 根据文件流内容推断 MIME 后生成路径
generatePathContent($file, string $filename, ?string $mime = null, ?string $prefix = null): array
// 校验扩展名和大小，内置黑名单
validateFile(string $extension, ?int $size = 0): void
```

上传相关模块如果只需要“校验 + 生成存储路径”，应直接复用这里，不要重复维护文件类型白名单。

### Notice

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\Notice

// 向单个用户发送站内通知
sendToUser(string $userModel, int $userId, string $title, ?string $content = null, array $options = []): SystemNotice
// 批量标记已读
markRead(string $userHas, int $userId, ?array $ids = null)
// 获取未读数
getUnreadCount(string $userHas, int $userId): int
// 获取通知统计
getStats(string $userHas, int $userId): array
// 分页获取通知列表
getList(array $params = [], ?string $userModel = null, ?int $userId = null): array
// 获取通知详情
getDetail(int $noticeId, ?string $userModel = null, ?int $userId = null): ?array
// 删除通知
delete(string $userHas, int $userId, ?array $ids = null)
```

这是通用通知接口。只要业务对象能用 `user_has + user_id` 表达，就可以复用它。

### SystemNotice

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\SystemNotice

// 统一按目标类型批量发送通知，targetType 支持 all、role、dept、user
sendBatch(string $targetType, array $targetIds, string $title, ?string $content = null, array $options = []): int
// 向全部启用状态的后台管理员发送通知
sendToAll(string $title, ?string $content = null, array $options = []): int
// 按角色批量发通知
sendToRoles(array $roleIds, string $title, ?string $content = null, array $options = []): int
// 按部门批量发通知，并自动包含子部门用户
sendToDepartments(array $deptIds, string $title, ?string $content = null, array $options = []): int
// 按明确用户 ID 列表发送通知
sendToUsers(array $userIds, string $title, ?string $content = null, array $options = []): int
```

这是面向系统管理员用户的批量通知门面。其他后台业务模块如果目标用户就是 `SystemUser`，优先走它。

### Bulletin

状态：`稳定可用`

```php
// App\System\Service\Bulletin

// 按用户、公告类型、已读状态等条件返回当前可见公告
getList(?string $userModel = null, ?int $userId = null, array $params = []): array
// 返回公告总数、已读数、未读数
getStats(?string $userModel = null, ?int $userId = null, array $params = []): array
// 为指定用户写入一条公告已读记录
markRead(string $userModel, int $userId, int $bulletinId): void
```

公告适合“面向一组用户长期展示”的消息，不适合即时单发提醒。

### Memo

状态：`稳定可用`

```php
// App\System\Service\Memo

// 按用户返回备忘录分页列表，支持 tab、优先级、关键词筛选
getList(string $userModel, int $userId, array $params = []): array
// 读取单条备忘录详情，并做用户归属校验
getDetail(int $memoId, string $userModel, int $userId): ?array
// 创建备忘录
create(string $userModel, int $userId, array $data): array
// 更新备忘录
update(int $memoId, string $userModel, int $userId, array $data): array
// 批量删除当前用户自己的备忘录
delete(string $userModel, int $userId, array $ids): void
// 切换完成状态，并自动维护 completed_at
toggleComplete(int $memoId, string $userModel, int $userId, bool $isCompleted = true): void
// 返回总数、已完成、待处理、已过期统计
getStats(string $userModel, int $userId): array
```

这是一个按用户模型隔离的轻量待办能力。其他模块如果要嵌入“我的备忘/待办”体验，可以直接复用。

### Visitor

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\Visitor

// 累计 PV、UV 和爬虫访问记录，type + id 用来标识统计主体
increment(ServerRequestInterface $request, string $type, string|int|null $id = null, string $driver = 'web', string $path = ''): void
```

用于累计 PV、UV 和爬虫访问记录。`type + id` 决定统计主体，适合文章、页面、频道等内容对象。

### SchedulerService

状态：`稳定可用`、`扩展入口`

```php
// App\System\Service\SchedulerService

// 把数据库里启用的计划任务记录转换成调度器运行所需的任务数组
buildJobs(): array
// 触发系统重新生成调度任务定义
generate(): void
// 扫描所有带 #[Scheduler] 注解的任务类，返回后台可选任务列表
getAttributeOptions(): array
```

如果模块定义了自己的 `Scheduler` 任务类，最终会通过这个 Service 聚合进系统调度。

### Stats

状态：`稳定可用`

```php
// App\System\Service\Stats

// 计算环比百分比
calculateRate($currentValue, $previousValue): float|int
```

这是简单统计辅助函数，适合复用在看板和报表模块。

## 对外开放的事件

### system.manage

状态：`稳定可用`、`扩展入口`

```php
// 事件名
system.manage

// 触发位置
App\System\Web\Manage::index()

// 事件对象
App\System\Event\ManageEvent

// 用途：给管理端壳层追加或重写管理应用定义
// 常见字段：name、title、routePrefix、apiBasePath、apiRoutePath、userMenus、upload、notice、map

// 读取当前管理端配置列表
getManages(): array
// 直接覆盖全部管理端配置
setManages(array $manages): void
// 追加一项管理端配置
addManage(array $manage): void
```

适用场景：一个模块需要在 `/manage/` 里追加新的后台壳层或自定义管理端配置。

### system.manage.config

状态：`稳定可用`、`扩展入口`

```php
// 事件名
system.manage.config

// 触发位置
App\System\Web\Manage::index()

// 事件对象
App\System\Event\ManageConfigEvent

// 用途：向管理端前端配置对象注入额外键值

// 获取当前配置数组
getConfig(): array
// 写入一个配置项
set(string $key, mixed $value): void
```

适用场景：给前端管理壳层注入模块自己的运行时配置，例如地图、开关、品牌信息、外部服务参数等。

### 模块内部注册的核心事件监听

```php
// 监听事件
scheduler.gen

// 注册位置
App\System\App::register()

// 作用：把 SchedulerService::buildJobs() 的结果写回调度器生成流程
```

这个事件不是 `System` 自己定义的业务事件，但它决定了系统计划任务如何被导出。

## 命令

```bash
php dux menu:sync [module] [app]
# 同步模块菜单到数据库

php dux menu:uninstall [module] [app]
# 从数据库卸载模块菜单
```

## 其他模块接入建议

- 需要保存模块设置时，优先用 `Config::getValue()` / `Config::setValue()`，不要重复建一套配置表。
- 需要给管理员发通知时，优先用 `SystemNotice`；需要面向任意用户模型发通知时，用 `Notice`。
- 需要上传或写入对象存储时，优先用 `Upload` 和 `Storage`，不要直接读配置拼客户端。
- 需要扩展管理端入口或前端运行时配置时，监听 `system.manage` 和 `system.manage.config`。
- 需要把模块后台菜单挂入系统时，在 `app.json` 中维护菜单声明，然后走 `menu:sync`。
