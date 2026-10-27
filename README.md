# Multitena — fase 1

Aplicación multitenencia inicial para gestionar empresas. Un superadministrador puede registrar una empresa; la operación crea automáticamente su sucursal **Central** y un usuario administrador asignado a esa sucursal. Cada administrador ve únicamente su empresa.

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

Abrí `http://127.0.0.1:8000/login`. El comando del superadministrador solicita nombre, correo y contraseña de forma interactiva. No se incluyen credenciales ni empresas de prueba.

Al crear una empresa, el correo sugerido es `admin@<nombre-normalizado>.com` (por ejemplo, Pepsi → `admin@pepsi.com`). Se puede editar para usar el dominio real. El superadministrador define la contraseña inicial y la comparte de forma segura. La creación de empresa, sucursal y usuario ocurre dentro de una transacción de base de datos.

## Pruebas

Desde `backend/`: `php artisan test`. Las pruebas usan SQLite en memoria y no cambian `bd/database.sqlite`.

## Alcance de esta fase

Incluye registro de empresas, sucursal Central automática, administrador inicial, inicio y cierre de sesión y paneles separados. La creación de más sucursales y la gestión de usuarios se podrán añadir en fases posteriores.
