# RunOrb Backend

RunOrb 后端服务源码

## 项目结构

| 目录 | 说明 | 技术栈 |
|------|------|--------|
| api-server/ | 后端 API 服务 | PHP 8.2 + Laravel |
| ws-server/ | PK WebSocket 服务 | PHP 8.2 + Swoole |
| admin-panel/ | Web 后台管理 | Vue.js |

## 服务器信息
- 服务器：DigitalOcean 新加坡 (129.212.236.200)
- Nginx + PHP-FPM 8.2 + MySQL 8.0 + Redis
- API 域名：api.runorb.us
- 管理后台：admin.runorb.us
- WebSocket：wss://api.runorb.us/pkroom

## 注意
- `api-server/` 排除了 public/ (1.6G)、Sql/ (1.1G)、vendor/ (71M)、storage/ (13M)
- `ws-server/` 排除了 vendor/ (47M)、public/ (38M)
- 这些目录需要在部署时通过 composer install 等命令恢复

## 文档
- [双端上架全流程指南](./上架全流程指南.md)
