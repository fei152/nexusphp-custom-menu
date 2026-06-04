# NexusPHP 自定义菜单插件快速安装包

适用：NexusPHP 1.10.2 原版项目。

## 先解压

上传压缩包后先解压，得到 `nexusphp-custom-menu-patch/` 目录：

```bash
tar -xzf nexusphp-custom-menu-patch.tar.gz
```

下面命令里的 `/path/to/nexusphp-custom-menu-patch` 指这个解压后的目录，不是压缩包本身。

## 宝塔 / 实机安装

宝塔、原生 Nginx + PHP-FPM、非 Docker 环境，一般直接运行：

```bash
bash /path/to/nexusphp-custom-menu-patch/install.sh /www/wwwroot/nexusphp
```

如果当前目录已经是 NexusPHP 项目根目录，也可以：

```bash
bash /path/to/nexusphp-custom-menu-patch/install.sh
```

宝塔要注意 PHP/Composer 路径。如果系统 `php` 不是站点 PHP 版本，先确认：

```bash
php -v
composer -V
```

必要时用宝塔 PHP 路径执行 Composer，例如 `/www/server/php/82/bin/php`。如果目录权限不够，用 `root` 运行安装脚本。

## Docker Compose 安装

如果使用 NexusPHP 自带 `docker-compose.yml`，项目根目录通常会挂载到 PHP 容器的 `/var/www/html`。这种情况用 Docker 脚本：

```bash
bash /path/to/nexusphp-custom-menu-patch/install-docker.sh /var/www/nexusphp
```

默认参数：

- PHP 服务名：`php`
- 容器内项目路径：`/var/www/html`

如果你改过服务名或容器路径，可以传第 2、3 个参数：

```bash
bash /path/to/nexusphp-custom-menu-patch/install-docker.sh /var/www/nexusphp php /var/www/html
```

Docker 脚本会先把插件包复制到项目目录里的 `.nexusphp-custom-menu-patch/`，让 PHP 容器能通过挂载看到它，然后在 PHP 容器内执行 Composer 和 Artisan。

## 脚本会做什么

1. 复制插件源码到 `packages/nexus-custom-menu`。
2. 复制后台管理资源到 `app/Filament/Resources/System/CustomMenuItemResource.php`。
3. 修改 `composer.json`，加入本地 path 仓库、PSR-4 自动加载和 `xiaomlove/nexusphp-menu` 依赖。
4. 把后台字段名从“最低可见等级”改成“最低访问等级”。
5. 执行 `composer update xiaomlove/nexusphp-menu -W`。
6. 执行 `php artisan plugin install xiaomlove/nexusphp-menu`。
7. 清理缓存。

## 后台入口

安装后进入后台：系统 -> 自定义菜单。

菜单文字按后台填写显示，支持 `en`、`chs`、`cht` 三种语言字段。当前语言有对应文本就显示对应文本，没有则取第一个非空文本。

## 权限说明

“最低访问等级”同时控制两件事：

- 等级不够时前台菜单不显示。
- 等级不够但直接输入该菜单 URL 时，按 404 处理。

这个拦截只在 NexusPHP 老前台页面执行，不拦截后台 Filament 页面、CLI、announce、scrape。

## 可选兼容补丁

`optional-compat` 里有两个文件：

- `app/Auth/NexusWebGuard.php`
- `app/Providers/Filament/AppPanelProvider.php`

这两个不是菜单插件核心文件，只是之前某些环境后台登录/Livewire 异常时用过的兼容改动。默认安装不会覆盖它们。

确实遇到后台登录或 Livewire 问题时，实机/宝塔执行：

```bash
bash /path/to/nexusphp-custom-menu-patch/install-optional-compat.sh /path/to/nexusphp
```

Docker 环境不建议直接跑这个脚本；如果需要，再按容器路径单独处理。

## 手工安装要点

如果不用脚本，核心步骤是：

```bash
cp -R files/packages/nexus-custom-menu /path/to/nexusphp/packages/
cp files/app/Filament/Resources/System/CustomMenuItemResource.php /path/to/nexusphp/app/Filament/Resources/System/
mkdir -p /path/to/nexusphp/app/Filament/Resources/System/CustomMenuItemResource/Pages
cp files/app/Filament/Resources/System/CustomMenuItemResource/Pages/ManageCustomMenuItems.php /path/to/nexusphp/app/Filament/Resources/System/CustomMenuItemResource/Pages/
php tools/configure-composer.php /path/to/nexusphp
php tools/update-labels.php /path/to/nexusphp
cd /path/to/nexusphp
composer update xiaomlove/nexusphp-menu -W
php artisan plugin install xiaomlove/nexusphp-menu
php artisan optimize:clear
```
