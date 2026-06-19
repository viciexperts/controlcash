# ControlCash

MVP en Laravel 12, Vue 3, Inertia, Tailwind y SQLite para llevar gastos personales y gastos compartidos por grupos.

## Funciones incluidas

- Auth por email/contrasena con Laravel Breeze.
- Auth con Google usando Laravel Socialite.
- Espanol por defecto y estructura preparada para i18n en `resources/js/i18n`.
- Dashboard con resumen diario, mensual, categorias y ultimos gastos.
- CRUD de categorias personales con color e icono.
- CRUD de gastos personales y de grupo.
- Creacion de grupos y gestion de miembros por email.
- Division igualitaria de gastos de grupo.
- Balance por miembro y sugerencias de pago para saldar cuentas.
- Registro de pagos saldados entre miembros.

## Arranque local

```bash
cd /Users/omarrodriguez/Sites/gastos-diarios
php artisan serve
npm run dev
```

La base de datos SQLite vive en `database/database.sqlite`.

## Deploy en Render con Docker

Usa estos valores:

```txt
Language: Docker
Root Directory: vacio
Dockerfile Path: ./Dockerfile
```

Variables recomendadas en Render:

```env
APP_NAME=ControlCash
APP_KEY=
APP_ENV=production
APP_DEBUG=false
APP_URL=https://controlcash.itcontinental.com
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_ES
LOG_CHANNEL=stderr
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/storage/database.sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
VITE_APP_NAME=ControlCash
VITE_APP_LOCALE=es
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://controlcash.itcontinental.com/auth/google/callback
```

Genera `APP_KEY` localmente con `php artisan key:generate --show` y pega el valor en Render.

Para conservar SQLite entre deploys, agrega un persistent disk en Render montado en:

```txt
/var/www/html/storage
```

## Google OAuth

Configura estas variables en `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

En Google Cloud, el redirect URI debe coincidir con el valor final de `GOOGLE_REDIRECT_URI`.

## Verificacion

```bash
php artisan test
npm run build
```
