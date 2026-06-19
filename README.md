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
DB_CONNECTION=pgsql
DB_HOST=ep-quiet-butterfly-adlxn8a6-pooler.c-2.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=
DB_SSLMODE=require
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
RECEIPT_FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=controlcash
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_VISIBILITY=private
VITE_APP_NAME=ControlCash
VITE_APP_LOCALE=es
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://controlcash.itcontinental.com/auth/google/callback
```

Genera `APP_KEY` localmente con `php artisan key:generate --show` y pega el valor en Render.

### Base de datos persistente en Render

Usa Neon Postgres para que la base viva fuera del contenedor de Render. Asi los datos no se borran cuando haces deploy o subes commits.

Variables necesarias en Render:

```env
DB_CONNECTION=pgsql
DB_HOST=ep-quiet-butterfly-adlxn8a6-pooler.c-2.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=tu_password_de_neon
DB_SSLMODE=require
```

No guardes `DB_PASSWORD` en git. Configuralo solo como Environment Variable en Render.

### Recibos en Cloudflare R2

Los archivos de recibos usan el disco `RECEIPT_FILESYSTEM_DISK`. Para guardar recibos en R2, configura estas variables en Render:

```env
RECEIPT_FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=tu_access_key_id_de_r2
AWS_SECRET_ACCESS_KEY=tu_secret_access_key_de_r2
AWS_DEFAULT_REGION=auto
AWS_BUCKET=controlcash
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_VISIBILITY=private
```

No guardes las llaves de R2 en git. Con `AWS_VISIBILITY=private`, ControlCash genera enlaces temporales para ver los recibos desde la app.

SQLite queda disponible para desarrollo local. Si decides usar SQLite en Render, tendrias que montar un Persistent Disk y apuntar `DB_DATABASE` dentro de ese disco:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/data/controlcash/database.sqlite
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
