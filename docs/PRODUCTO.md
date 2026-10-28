# Multitena: presentación y operación

## Qué resuelve

Una organización puede operar varias empresas y varias sucursales sin mezclar contactos ni oportunidades. El superadministrador da de alta una empresa en una sola operación: crea la sucursal Central y su primer administrador. La empresa puede crecer con nuevas sucursales y administradores; cada equipo ve solo los datos que le corresponden.

## Roles

| Rol | Alcance | Acciones principales |
| --- | --- | --- |
| Superadministrador | Todas las empresas, sin acceso al CRM privado | Alta, edición y suspensión de empresas; administradores de empresa; indicadores y actividad por empresa |
| Administrador de empresa | Una empresa y todas sus sucursales | Sucursales, usuarios de sucursal, CRM, exportaciones y actividad |
| Administrador de sucursal | Una sucursal | Contactos, oportunidades, notas y exportaciones de su sucursal |

## Guion breve de demostración

1. Ingresar como superadministrador y crear una empresa. Mostrar que aparecen Central y su administrador.
2. Entrar como administrador de empresa; crear una sucursal y un administrador de sucursal.
3. Crear un contacto, registrar una nota, abrir una oportunidad y moverla a otra etapa.
4. Filtrar contactos y oportunidades; exportar CSV.
5. Entrar como administrador de sucursal y comprobar que solo ve su sucursal. Suspenderlo y comprobar que pierde acceso incluso con una sesión abierta.
6. Mostrar el registro de actividad y las empresas DEMO identificadas.

## Datos y respaldo

La SQLite local está en `bd/database.sqlite`. Docker usa el volumen `multitena_app_data`. Son bases distintas. Antes de actualizar, hacé un respaldo consistente de SQLite (por ejemplo, con `VACUUM INTO`) y conservá también la clave `APP_KEY`; sin esa clave no se pueden recuperar datos cifrados por Laravel. El inicio del contenedor guarda su clave en el mismo volumen. Las migraciones agregan tablas y columnas sin borrar registros. Las bajas operativas se hacen por suspensión, para conservar historial.

## Preparación para ofrecerlo a clientes

El alcance funcional de esta versión cubre administración multitenencia y CRM. Para operar públicamente se necesita configurar dominio y HTTPS, un servidor web de producción, correo SMTP, respaldos automáticos probados con restauración, monitoreo y políticas de privacidad y retención de datos acordes al cliente. La Compose incluida ejecuta una demostración local, no un despliegue público. La facturación, integraciones externas y reglas particulares de cada industria deben definirse con cada comprador antes de ofertarlas como parte del producto.
