# RMS Social Hub

Plataforma centralizada de gestión de redes sociales para **Raven Marketing Services**. Permite publicar Posts, Reels y Stories en múltiples Facebook Fan Pages desde un solo lugar, con scheduling, RBAC por roles y manejo automático de tokens.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 10 |
| Frontend | Vue 3 + Inertia.js |
| Estilos | Tailwind CSS |
| Base de datos | PostgreSQL |
| Colas async | Redis |
| API externa | Meta Graph API v21 |
| Auth | Laravel Breeze (sesión) |

---

## Funcionalidades principales

- **Publicación multi-página** — Posts, Reels y Stories en múltiples Fan Pages simultáneamente
- **Scheduling** — Calendario visual con FullCalendar, publicación programada con precisión de minutos
- **State machine de posts** — `Draft → Scheduled → Sending → Published | Failed`
- **Retry automático** — Backoff exponencial (5 / 15 / 30 min), máximo 3 intentos
- **Token management** — Long-lived tokens (60 días), auto-refresh diario, alertas de expiración
- **RBAC con 3 roles** — `super-admin`, `admin`, `editor` con permisos por página
- **Notificaciones** — In-app + email para publicaciones exitosas, fallos y tokens expirados
- **Audit log inmutable** — Registro completo de acciones con filtros por fecha, usuario y página
- **Seguridad** — Tokens encriptados en BD, HTTPS enforced, headers de seguridad, rate limiting

---

## Roles y permisos

| Rol | Permisos |
|-----|---------|
| `super-admin` | Acceso total, gestión de credenciales Meta App |
| `admin` | Gestión de páginas, usuarios y editores |
| `editor` | Crear y schedular posts en páginas asignadas |

---

## Requisitos

- PHP 8.1+
- Composer
- Node.js 20+
- PostgreSQL 14+
- Redis 6+
- Meta Developer App (App ID + App Secret)

---

## Instalación local

```bash
# 1. Clonar el repositorio
git clone https://github.com/glacayo/rms-social-hub.git
cd rms-social-hub

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias JS
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar .env con tus credenciales
# DB_CONNECTION=pgsql
# DB_DATABASE=rms_social_hub
# REDIS_HOST=127.0.0.1
# FACEBOOK_APP_ID=tu_app_id
# FACEBOOK_APP_SECRET=tu_app_secret
# FACEBOOK_REDIRECT_URI=http://localhost/facebook/callback

# 6. Crear tablas y datos de prueba
php artisan migrate --seed

# 7. Compilar assets (desarrollo)
npm run dev

# 8. Levantar queue worker
php artisan queue:work redis --queue=publishing,token-refresh,notifications,default

# 9. Levantar scheduler (en otra terminal)
php artisan schedule:work
```

---

## Usuarios de prueba (seeder)

| Email | Contraseña | Rol |
|-------|-----------|-----|
| superadmin@rms.test | password | super-admin |
| admin@rms.test | password | admin |
| editor@rms.test | password | editor |

---

## Arquitectura de módulos

```
app/
├── Modules/
│   ├── Facebook/
│   │   ├── Contracts/          # FacebookApiClientInterface
│   │   ├── DTOs/               # TokenDTO, PageDTO, PublishResponseDTO
│   │   ├── Services/           # FacebookApiClient, OAuthService, TokenManager
│   │   └── PageRepository.php
│   └── Publisher/
│       ├── PostStateMachine.php
│       ├── PublishService.php
│       ├── SchedulerService.php
│       ├── RetryPolicy.php
│       └── MediaValidator.php
├── Jobs/
│   ├── PublishPostJob.php       # Queue: publishing
│   └── RefreshTokenJob.php     # Queue: token-refresh (diario 02:00 UTC)
├── Notifications/
│   ├── PostPublishedNotification.php
│   ├── PostFailedNotification.php
│   └── TokenExpiredNotification.php
└── Services/
    └── AuditLogger.php
```

---

## Queues configuradas

| Queue | Propósito | Workers recomendados |
|-------|-----------|---------------------|
| `publishing` | Publicación de posts | 3 |
| `token-refresh` | Renovación de tokens | 1 |
| `notifications` | Notificaciones in-app y email | 1 |
| `default` | Tareas generales | 1 |

---

## Tests

```bash
# Correr toda la suite
php artisan test

# Solo tests unitarios
php artisan test tests/Unit

# Solo tests de integración
php artisan test tests/Feature
```

La suite usa `FakeFacebookApi` — ningún test hace llamadas reales a la Meta Graph API.

**Cobertura estimada:**
- Módulo Publisher: ~85%
- Módulo Facebook: ~80%

---

## Variables de entorno requeridas

| Variable | Descripción |
|----------|-------------|
| `FACEBOOK_APP_ID` | ID de tu Meta App |
| `FACEBOOK_APP_SECRET` | Secret de tu Meta App |
| `FACEBOOK_REDIRECT_URI` | URL de callback OAuth |
| `DB_CONNECTION` | `pgsql` |
| `QUEUE_CONNECTION` | `redis` |
| `MAIL_*` | Config de email para notificaciones |

> ⚠️ Nunca commitees el archivo `.env`. Está incluido en `.gitignore`.

---

## Licencia

Proyecto privado de **Raven Marketing Services**. Todos los derechos reservados.
