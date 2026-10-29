# Multitena

Plataforma multitenencia de administración y CRM para empresas con una o varias sucursales. Cada empresa inicia con una sucursal **Central** y un administrador. Los contactos, oportunidades y notas quedan dentro de su empresa; un administrador de sucursal solo accede a su sucursal.

## Funciones

- **Superadministrador:** crea empresas, revisa indicadores globales, edita datos de contacto, activa o suspende empresas y administra sus administradores. No entra al CRM privado de las empresas.
- **Administrador de empresa:** mantiene sucursales y administradores de sucursal, ve el CRM completo de su empresa y consulta el registro de actividad.
- **Administrador de sucursal:** gestiona contactos, oportunidades y seguimiento de su sucursal.
- **CRM:** fichas de contacto, estados, organizaciones, origen, notas con autor y fecha, oportunidades con etapa, monto y cierre estimado; búsqueda, filtros, paginación y exportación CSV.
- **Acceso:** inicio y cierre de sesión, límite de intentos, cambio y recuperación de contraseña, bloqueo inmediato de cuentas y empresas suspendidas. La sucursal Central no se puede suspender ni renombrar; una empresa debe conservar al menos un administrador activo.
- **Datos:** migraciones aditivas, SQLite persistente, datos DEMO etiquetados y registro de cambios. La desactivación conserva los registros para consulta y reactivación.
- **Personal:** el superadministrador registra empleados individuales o importa un CSV (`name,email,password`, máximo 1000 filas). Quedan pendientes hasta que el administrador de su empresa los apruebe o rechace. Cada empresa configura categorías, permisos de lectura y escritura del CRM, dominios de correo y acceso móvil.
- **API móvil:** Laravel Sanctum emite tokens por sesión y dispositivo con vencimiento. La empresa puede exigir aprobación de dispositivos nuevos, consultar sus sesiones y revocar dispositivos. Las solicitudes pendientes se guardan en `access_notifications` para una futura integración push; aún no se envían avisos push externos.

## API móvil

`POST /api/login` recibe `email`, `password`, `company_code`, `device_id` y `device_name`. El código de la empresa se encuentra en `companies.code`; los códigos de las empresas existentes se generan con la migración. El identificador de dispositivo solo identifica el equipo, nunca sustituye la contraseña. La respuesta entrega un token Bearer y su vencimiento. Con el token: `GET /api/session`, `POST /api/logout`, `GET/POST /api/contacts` y `GET/POST /api/opportunities`. Los empleados necesitan una categoría con el permiso correspondiente (`contacts.read`, `contacts.write`, `opportunities.read`, `opportunities.write`). El superadministrador no accede al CRM por API.

Ejecutá `php artisan schedule:work` o programá `php artisan schedule:run` para que las sesiones vencidas se marquen como expiradas. La API rechaza tokens vencidos incluso si el programador todavía no corrió. La revocación del dispositivo invalida sus tokens inmediatamente.

## Requisitos y estructura

PHP 8.4, Composer 2 y las extensiones `pdo_sqlite`, `sqlite3`, `mbstring` y `openssl`, o Docker con Compose.

- `backend/`: Laravel 13, lógica, migraciones y pruebas.
- `frontend/`: vistas Blade con HTML, CSS y JavaScript sin compilación.
- `bd/`: SQLite local y respaldos locales; no se publican en Git.
- `Dockerfile`, `compose.yaml`, `docker/`: ejecución en contenedor con volumen SQLite propio.

## Inicio local

Desde `backend/`:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path ..\bd\database.sqlite)) { New-Item -ItemType File ..\bd\database.sqlite }
php artisan migrate
php artisan app:create-super-admin
php artisan serve
```

Abrí `http://127.0.0.1:8000/login`. Al actualizar una instalación existente, hacé un respaldo de `bd/database.sqlite` y ejecutá `php artisan migrate --force`. No uses `migrate:fresh` sobre datos reales.

## Inicio con Docker

Desde la raíz del repositorio, en la terminal de VS Code:

```powershell
docker compose up -d --build
docker compose exec app php artisan app:create-super-admin
docker compose exec app php artisan app:seed-demo
```

Abrí `http://127.0.0.1:8001/login`. `docker compose ps` muestra el estado; `docker compose logs -f app` muestra el registro y `docker compose down` detiene el contenedor. Los datos del contenedor están en el volumen `multitena_app_data` y no modifican `bd/database.sqlite` de Windows. No uses `docker compose down -v` si querés conservarlos.

VS Code incluye tareas para iniciar Docker, ejecutar las pruebas y abrir el servidor Laravel local. Están en `.vscode/tasks.json` y aparecen en **Terminal → Ejecutar tarea**.

La primera ejecución genera una clave de aplicación dentro del volumen y aplica las migraciones. El contenedor usa el servidor integrado de PHP y el puerto queda limitado a `127.0.0.1`, por lo que esta configuración está destinada a desarrollo y demostración local.

## Datos DEMO

`php artisan app:seed-demo` (o `docker compose exec app php artisan app:seed-demo`) crea dos empresas **DEMO**, sus sucursales y usuarios, más cuatro contactos y cuatro oportunidades de ejemplo. Los correos de prueba usan `.test` y los registros muestran la etiqueta `DEMO`. El comando imprime contraseñas aleatorias solo al crear cada usuario; guardalas. Si lo ejecutás otra vez, no duplica ni reemplaza registros. Solo funciona en `local` o `testing`.

## Correo y recuperación de acceso

El enlace “¿Olvidaste tu contraseña?” usa tokens de un solo uso y el sistema de correo de Laravel. Por defecto `MAIL_MAILER=log` registra el mensaje en los logs para pruebas locales. Para enviar correos reales, configurá `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS` en `backend/.env` o como variables del contenedor. No incluyas secretos en Git. El formulario no indica si una dirección está registrada.

## Pruebas

Desde `backend/`:

```powershell
php artisan test
php artisan view:cache
```

Las pruebas usan SQLite en memoria y no cambian `bd/database.sqlite`. El flujo de demostración y los criterios para preparar una instalación comercial están en [docs/PRODUCTO.md](docs/PRODUCTO.md).
