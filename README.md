<div align="center">

# DuxPHP Admin

![DuxPHP Admin](https://img.shields.io/badge/DuxPHP-Admin-blue?style=for-the-badge&logo=php)
![Version](https://img.shields.io/badge/version-2.0.13-brightgreen?style=for-the-badge)
![License](https://img.shields.io/badge/license-MIT-orange?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)

**🚀 基于 DuxLite 和 DVHA PRO 的开箱即用中后台管理系统**

*用最流行的技术栈，享受最便捷的开发体验*

**前后端一体化 • 运行时编译 • 修改即生效 • 免去繁琐的前端工具链**

[🎯 快速开始](#-快速开始) • [📚 文档](#-文档) • [🎨 特性](#-核心特性) • [🛠️ 安装](#️-安装指南) • [🤝 贡献](#-参与贡献)

</div>

---

## ✨ 为什么选择 DuxPHP Admin？

### 🎯 **传统开发的现代化升级**
不是前后端分离，而是**前后端一体化**！Vue 文件和 PHP 代码放在一起，就像传统 PHP 开发一样简单直观。

### ⚡ **修改即生效，告别编译等待**
运行时编译技术，修改 Vue 文件后刷新页面即可看到效果，**无需 webpack、vite 等前端工具链**。

### 🛠️ **开箱即用的完整解决方案**
基于 [DuxLite](https://github.com/duxweb/dux-lite) 框架和 [DVHA PRO](https://duxweb.github.io/dvha/) 组件库，集成用户管理、权限控制、数据管理等核心功能。

### 🎨 **最流行的技术栈**
- **后端**: PHP 8.2+ + DuxLite 框架
- **前端**: Vue 3 + Naive UI + TypeScript
- **开发体验**: 传统 PHP 开发的便捷性 + 现代前端的强大功能

---

## 🎨 核心特性

### 🔐 完整的权限管理系统
- **用户管理** - 完整的用户注册、登录、权限分配
- **角色管理** - 灵活的角色权限配置
- **部门管理** - 支持树形结构的部门组织
- **菜单管理** - 动态菜单配置与权限控制

### 📊 强大的数据管理
- **动态表单** - 可视化表单设计器
- **数据配置** - 灵活的数据模型配置
- **API 接口** - 自动生成 RESTful API
- **权限控制** - 细粒度的数据访问控制

### 🎯 系统管理功能
- **系统设置** - 灵活的配置参数管理
- **文件管理** - 完整的文件上传与管理
- **日志管理** - 详细的操作日志记录
- **API 文档** - 自动生成的 API 文档

### 🌐 国际化支持
- **多语言** - 完整的国际化解决方案
- **本地化** - 支持自定义语言包
- **时区支持** - 灵活的时区配置

### 🎨 现代化前端
- **Vue 3** - 基于最新的 Vue 3 框架
- **Naive UI** - 美观的 UI 组件库
- **响应式设计** - 完美支持移动端
- **TypeScript** - 类型安全的开发体验

## 🛠️ 技术栈

### 后端技术
- **PHP 8.2+** - 现代化的 PHP 语言特性
- **DuxLite** - 轻量级 PHP 框架
- **Eloquent ORM** - 强大的数据库 ORM
- **Slim Framework** - 高性能的微框架

### 前端技术
- **Vue 3** - 渐进式 JavaScript 框架
- **Naive UI** - 现代化的 Vue 3 组件库
- **DVHA PRO** - 专业的管理后台组件库
- **TypeScript** - 类型安全的 JavaScript

### 数据库支持
- **MySQL** - 主要支持的数据库
- **PostgreSQL** - 完整支持
- **SQLite** - 开发环境支持

## 🚀 快速开始

### 环境要求

| 环境 | 版本要求 |
|------|----------|
| PHP | 8.2+ |
| Composer | 2.0+ |
| MySQL | 5.7+ / 8.0+ |

### 一键安装

```bash
# 克隆项目
git clone https://github.com/your-username/dux-php-admin.git
cd dux-php-admin

# 安装 PHP 依赖
composer install

# 配置数据库
编辑 config/database.toml 文件

# 运行数据库迁移
php dux db:sync

# 启动开发服务器
php -S localhost:8000 -t public
```

## 📖 使用指南

### 基本配置

编辑 `config/use.toml` 文件进行基本配置：

```toml
[app]
name = "DuxPHP Admin"
debug = true
timezone = "Asia/Shanghai"
secret = "your-secret-key"
domain = "http://localhost:8000"
```

```toml
[database]
host = "localhost"
port = 3306
database = "dux_admin"
username = "root"
password = "password"
```

### API 使用

```javascript
// 获取数据列表
const response = await fetch('/api/data/users');
const data = await response.json();

// 创建数据
const response = await fetch('/api/data/users', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        name: '李四',
        email: 'lisi@example.com'
    })
});
```

## 🎯 核心模块

### 系统管理模块

| 功能 | 描述 | 状态 |
|------|------|------|
| 用户管理 | 用户增删改查、状态管理 | ✅ |
| 角色管理 | 角色权限配置 | ✅ |
| 部门管理 | 树形部门结构 | ✅ |
| 菜单管理 | 动态菜单配置 | ✅ |
| 权限管理 | 细粒度权限控制 | ✅ |
| 系统设置 | 参数配置管理 | ✅ |
| 文件管理 | 文件上传与管理 | ✅ |
| 日志管理 | 操作日志记录 | ✅ |

### 数据管理模块

| 功能 | 描述 | 状态 |
|------|------|------|
| 数据配置 | 动态数据模型配置 | ✅ |
| 表单设计 | 可视化表单设计器 | ✅ |
| 表格设计 | 动态表格配置 | ✅ |
| API 生成 | 自动生成 RESTful API | ✅ |
| 权限控制 | 数据访问权限控制 | ✅ |

## 🔧 CLI 工具

DuxPHP Admin 提供了强大的命令行工具：

```bash
# 查看所有可用命令
php dux

# 数据库相关
php dux db:sync          # 同步数据库结构
php dux db:backup        # 备份数据库
php dux db:restore       # 恢复数据库

# 开发工具
php dux route:list      # 查看路由列表
```

## 📚 文档

### 📖 完整文档
- **[DuxLite 框架文档](https://github.com/duxweb/dux-lite)** - 基础框架文档
- **[DVHA PRO 组件库](https://duxweb.github.io/dvha/)** - 前端组件库文档
- **[用户手册](docs/user-guide.md)** - 完整的使用指南
- **[开发文档](docs/development.md)** - 开发者指南
- **[API 文档](docs/api.md)** - 完整的 API 参考

### 🎯 快速链接
- **[配置说明](docs/configuration.md)** - 系统配置详解
- **[权限系统](docs/permissions.md)** - 权限管理说明
- **[数据管理](docs/data-management.md)** - 数据管理功能
- **[部署指南](docs/deployment.md)** - 生产环境部署

## 🤝 参与贡献

我们欢迎所有形式的贡献！

### 贡献方式

1. **Fork** 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 **Pull Request**

### 开发规范

- 遵循 **PSR-12** 编码规范
- 编写必要的测试用例
- 更新相关文档
- 确保所有测试通过

### 问题反馈

如果您发现任何问题或有改进建议，请：

1. 查看 [Issues](https://github.com/your-username/dux-php-admin/issues) 是否已有相关问题
2. 创建新的 Issue 并详细描述问题
3. 提供复现步骤和环境信息

## 📊 项目统计

![GitHub stars](https://img.shields.io/github/stars/your-username/dux-php-admin?style=social)
![GitHub forks](https://img.shields.io/github/forks/your-username/dux-php-admin?style=social)
![GitHub issues](https://img.shields.io/github/issues/your-username/dux-php-admin)
![GitHub license](https://img.shields.io/github/license/your-username/dux-php-admin)

## 🙏 致谢

感谢以下优秀的开源项目：

- [DuxLite](https://github.com/duxweb/dux-lite) - 基础框架
- [DVHA PRO](https://duxweb.github.io/dvha/) - 前端组件库
- [Vue.js](https://vuejs.org/) - 前端框架
- [Naive UI](https://www.naiveui.com/) - UI 组件库
- [Eloquent ORM](https://laravel.com/docs/eloquent) - 数据库 ORM

## 📄 开源协议

本项目基于 [MIT 协议](LICENSE) 开源，您可以自由使用、修改和分发。

## 👥 作者

**DuxWeb 团队**

- 🌐 官网: [https://www.dux.cn](https://www.dux.cn)
- 📧 邮箱: admin@dux.cn
- 🐙 GitHub: [@duxweb](https://github.com/duxweb)

---

<div align="center">

**⭐ 如果这个项目对您有帮助，请给我们一个 Star！**

*您的支持是我们持续改进的动力*

[🔝 回到顶部](#duxphp-admin)

</div>