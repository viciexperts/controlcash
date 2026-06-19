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
DB_DATABASE=/var/data/controlcash/database.sqlite
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

### Persistencia de datos en Render

Render reemplaza el filesystem del contenedor en cada deploy. Si no agregas un Persistent Disk, la base SQLite se vuelve efimera y los datos pueden perderse cuando subes cambios.

Para conservar SQLite entre deploys, agrega un Persistent Disk en el servicio de Render:

```txt
Name: controlcash-storage
Mount path: /var/data
Size: 1 GB o mas
```

Con ese mount path, esta variable debe quedar asi:

```env
DB_DATABASE=/var/data/controlcash/database.sqlite
```

Si `DB_DATABASE` apunta a `/var/www/html/...` o a cualquier ruta que no este dentro del Persistent Disk, la base de datos se perdera en cada deploy.

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
