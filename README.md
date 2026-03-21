<h1 align="center">Dux PHP Admin</h1>

<p align="center">
  <strong>🚀 PHP 8.4 + Vue 3 的一体化后台解决方案</strong>
</p>

<p align="center">
  传统全栈的顺手体验 + 前后端分离的规范化优势，基于一体化模块架构
</p>

<p align="center">
  <a href="https://pam.docs.dux.plus/" target="_blank">📖 中文文档</a> |
  <a href="https://github.com/duxweb/dux-php-admin" target="_blank">🏠 GitHub</a> |
  <a href="https://cloud.dux.plus" target="_blank">云市场</a> |
  <a href="https://www.dux.cn" target="_blank">🌐 官网</a>
</p>

<p align="center">
  <img alt="PHP Version" src="https://img.shields.io/badge/php-8.4+-blue.svg" />
  <img alt="License" src="https://img.shields.io/badge/License-MIT-green.svg" />
  <img alt="Version" src="https://img.shields.io/badge/version-v1.0-orange.svg" />
  <img alt="Stars" src="https://img.shields.io/github/stars/duxweb/dux-php-admin?style=social" />
</p>

---

## ✨ 核心特性

- 🚀 **一体化模块架构**：后端接口与前端页面在同一模块中组织，结构清晰
- ⚡ **基座模式**：页面放进模块即可访问，修改后刷新生效，无需每页打包
- 🔐 **权限与菜单联动**：权限节点与菜单名称一致，登录后自动过滤无权限菜单
- 📊 **资源化后端**：资源类自动生成 CRUD、路由与权限，统一响应结构
- 🧩 **系统模块开箱即用**：用户、角色、部门、日志、存储、任务调度等常用功能
- 🛠️ **命令行工具**：db:sync、menu:sync、route:list、permission:list 一站式支持

## 🏗️ 架构理念（直观理解）

你可以把它理解为 **iOS + App** 或 **微信 + 小程序** 的关系：

- **后端是基座**：路由、权限、菜单、接口统一管理
- **页面是模块**：放在模块目录中，按需加载

既保留传统全栈的直观体验，又保持前后端分离的规范化优势。

## 🚀 快速开始（最短流程）

### 1) 确认环境

```bash
php -v
composer self-update
```

> 请勿使用 Composer 镜像源，保持官方源即可。

### 2) 获取源码并安装依赖

```bash
git clone https://github.com/duxweb/dux-php-admin.git dux-php-admin
cd dux-php-admin
composer install
```

### 3) 配置数据库

编辑 `config/database.toml`：

```toml
[db.drivers.default]
driver = "mysql"
host = "localhost"
database = "dux_admin"
username = "root"
password = "root"
port = 3306
prefix = "app_"
```

### 4) 初始化数据库与菜单

```bash
php dux db:sync
php dux menu:sync
```

### 5) 启动服务

```bash
php -S localhost:8000 -t public
```

访问后台：

- http://localhost:8000/manage/
- 默认账号：`admin / admin`

> 首次登录后请立即修改默认密码。

## 📦 前端基座更新（可选）

只有在修改 `web/` 目录或升级前端依赖时才需要更新基座：

```bash
bun install
bun run build
```

构建产物输出到：

```
<项目目录>/public/static/web
```

## 📚 文档

- 快速开始：https://pam.docs.dux.plus/guide/getting-started
- 项目介绍：https://pam.docs.dux.plus/guide/introduction
- 开发指南：https://pam.docs.dux.plus/dev/quick-start/environment

## 🤝 参与贡献

欢迎提交 Issues 和 Pull Requests。

- Issues：https://github.com/duxweb/dux-php-admin/issues
- Discussions：https://github.com/duxweb/dux-php-admin/discussions
- PR：https://github.com/duxweb/dux-php-admin/pulls

## 📄 开源协议

本项目基于 MIT 协议开源。
