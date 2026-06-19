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
