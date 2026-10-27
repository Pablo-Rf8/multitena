# Multitena

Aplicación multitenencia para gestionar empresas y sucursales. Un superadministrador registra empresas; la operación crea automáticamente su sucursal **Central** y un administrador. El administrador de empresa puede agregar sucursales y administradores asignados a ellas. Cada usuario ve solo los datos de su ámbito.

## Requisitos

- PHP 8.4 con `pdo_sqlite`, `sqlite3`, `mbstring` y `openssl`.
- Composer 2.

## Estructura

- `backend/`: Laravel 13, rutas, modelos, migraciones, autenticación y pruebas.
- `frontend/`: vistas Blade (HTML), CSS y JavaScript sin compilación.
- `bd/`: SQLite local. El archivo de datos se excluye de Git para conservar datos y credenciales.

## Instalación

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

Abrí `http://127.0.0.1:8000/login`. El comando del superadministrador solicita nombre, correo y contraseña de forma interactiva. No se incluyen credenciales en Git.

Al crear una empresa, el correo sugerido es `admin@<nombre-normalizado>.com` (por ejemplo, Pepsi → `admin@pepsi.com`). Se puede editar para usar el dominio real. El superadministrador define la contraseña inicial y la comparte de forma segura. La creación de empresa, sucursal y usuario ocurre dentro de una transacción de base de datos.

## Datos de demostración

Para una instalación local, ejecutá `php artisan app:seed-demo` desde `backend/`. Crea **Pepsi (DEMO)** y **Coca-Cola (DEMO)**, con sucursales y usuarios para probar ambos niveles de administrador. El comando muestra contraseñas aleatorias una sola vez: guardalas en un lugar seguro. Puede ejecutarse nuevamente sin duplicar ni modificar registros existentes. Se niega a ejecutarse en producción. Los registros DEMO tienen una marca visible y sus correos usan el dominio reservado `.test`.

La base `bd/database.sqlite` es local y está fuera de Git. Al clonar el repositorio se crea una base nueva; los datos DEMO no se publican.

## Pruebas

Desde `backend/`: `php artisan test`. Las pruebas usan SQLite en memoria y no cambian `bd/database.sqlite`.

## Funciones disponibles

Incluye registro de empresas, sucursal Central automática, creación de sucursales adicionales, administradores de sucursal, inicio y cierre de sesión, cambio de contraseña y paneles separados. La edición o eliminación de empresas y sucursales todavía no está implementada.
