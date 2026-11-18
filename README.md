# 记账本（Account Book）

Laravel 13 + MySQL 8 + Vue 3 移动端优先记账应用。支持邮箱验证注册、Sanctum 同域 Cookie 登录、周/月/年收入统计。

## 环境要求

- PHP 8.3+
- Composer
- MySQL 8.0+（`utf8mb4`）
- Node.js 18+

## 快速开始

### 1. 创建 MySQL 数据库

```sql
CREATE DATABASE account_book CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. 配置环境

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 中的 `DB_*` 与 `APP_URL`（需与访问地址一致，便于邮箱验证链接）。

本地邮件调试可将 `MAIL_MAILER=log`，验证链接在 `storage/logs/laravel.log` 中查看；或使用 Mailpit 等 SMTP。

### 3. 安装与迁移

```bash
composer install
php artisan migrate
npm install
php artisan storage:link
```

上传附件保存在 `storage/app/public`，需上述 `storage:link` 才能通过 `/storage/...` 访问。

### 4. 开发运行

终端一：

```bash
php artisan serve --host=127.0.0.1 --port=8081
```

终端二：

```bash
npm run dev
```

浏览器打开 `http://127.0.0.1:8081`。

### 总后台（Filament）

访问：**http://127.0.0.1:8081/admin**（与用户 SPA 分开登录）

```bash
php artisan db:seed --class=AdminUserSeeder
```

| 字段 | 值 |
|------|-----|
| 邮箱 | `admin@localhost` |
| 密码 | `admin123456` |

功能：**创建/编辑用户**（自动建默认账本）、**封号/解封**；**前台用户登录记录**（IP、设备、时间）；流水/账本/分类只读；仪表盘统计。

### 测试账号（本地）

```bash
php artisan db:seed --class=TestUserSeeder
```

| 字段 | 值 |
|------|-----|
| 邮箱 | `test@example.com` |
| 密码 | `password` |

邮箱已标记为已验证，可直接登录。

### 演示流水（折线图）

```bash
php artisan db:seed --class=DemoTransactionsSeeder
```

为 `test@example.com` 补全默认分类，并生成近 35 天、**多分类** 收入/支出流水，便于在「统计」页查看折线图与分类占比。

产品七模块对照与路线图见 [docs/PRODUCT_MODULES.md](docs/PRODUCT_MODULES.md)。

## 主要功能

- 注册 + 邮箱验证后登录
- 记一笔（收入/支出 + 分类，可附图片/PDF，最多 5 个、单文件 ≤10MB）
- 流水列表与删除
- 统计：周 / 月 / 年收入及分类明细
- 「我的」内财务工具：存款 / 贷款计算器；白天·黑夜主题（默认黑夜）
- 注册时自动创建默认账本与分类

## API 概要（需登录且已验证邮箱）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/me` | 当前用户与默认账本 |
| PATCH | `/api/me` | 更新昵称、生日、移除头像 |
| POST | `/api/me/avatar` | 上传头像（≤2MB 图片） |
| PUT | `/api/me/password` | 修改密码（需 `current_password`） |
| GET | `/api/categories?type=income\|expense` | 分类列表（当前用户账本） |
| POST | `/api/categories` | 新建分类（`name`、`type`、`icon?`，仅本账本可见） |
| GET/POST | `/api/transactions` | 流水（列表支持 `from`、`to`、`type`、`category_id` 筛选） |
| GET | `/api/stats` | 统计（`period`、`metric`；可选 `from`+`to` 自定义区间、`category_id`） |

认证路由（Web + Session + CSRF）：

- `POST /register`、`POST /login`、`POST /logout`
- `GET /email/verify/{id}/{hash}`（邮件链接）
- `POST /email/verification-notification`（重发验证）

## 生产构建

```bash
npm run build
```

## 技术说明

- 数据表支持多账本，MVP 每用户一本（`users.settings.default_book_id`）。
- 流水按 `occurred_date` 聚合；`daily_summaries` 由 Observer 维护，便于扩展。