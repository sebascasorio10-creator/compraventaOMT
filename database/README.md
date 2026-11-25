# CompraVenta OMT — Base de datos y autenticación

## Tecnologías
- PHP 8+ con PDO.
- MySQL 8+ / MySQL Workbench.
- XAMPP para ejecutar Apache y MySQL localmente.
- Bootstrap 5.3 para las vistas de registro y administración.

## Estructura
- `database/compra_venta.sql`: crea la base de datos y sus relaciones.
- `database/migracion_verificacion_correo.sql`: agrega la verificación obligatoria de correo a las cuentas.
- `config/database.php`: conexión PDO local. Si necesitas credenciales propias, crea `config/database.local.php` (está ignorado por Git).
- `includes/bootstrap.php`: sesión, protección CSRF y escape de salida.
- `registro_comprador.php` y `registro_vendedor.php`: formularios y guardado de cuentas.
- `login.php` y `auth/logout.php`: inicio y cierre de sesión.
- `admin/usuarios.php`: panel protegido para consultar, buscar y filtrar usuarios.
- `scripts/crear_admin.php`: crea la primera cuenta administradora desde terminal.

## Instalación paso a paso en Windows (XAMPP + Visual Studio Code)
1. Instala XAMPP y Visual Studio Code.
2. En XAMPP Control Panel inicia **Apache** y **MySQL**.
3. Abre MySQL Workbench y conéctate al servidor local (normalmente host `127.0.0.1`, puerto `3306`, usuario `root`).
4. En Workbench abre `database/compra_venta.sql` y ejecuta el script completo con el botón del rayo.
5. Actualiza **Schemas**. Debe aparecer `compraventa_omt` con las tablas.
6. Copia el repositorio dentro de `C:\xampp\htdocs\compraventaOMT`.
7. Abre esa carpeta desde Visual Studio Code.
8. Si tu usuario MySQL tiene contraseña, crea `config/database.local.php` con este contenido:

```php
<?php
$host = '127.0.0.1';
$dbname = 'compraventa_omt';
$username = 'root';
$password = 'TU_CONTRASEÑA';
```

9. Abre `http://localhost/compraventaOMT/registro_comprador.php` o `registro_vendedor.php` para crear cuentas.
10. Para entrar al panel, crea primero un administrador desde PowerShell:

```powershell
cd C:\xampp\htdocs\compraventaOMT
C:\xampp\php\php.exe scripts/crear_admin.php
```

Escribe el correo, nombre y una contraseña de al menos 10 caracteres cuando el programa lo solicite. Después entra en `http://localhost/compraventaOMT/login.php`. Al iniciar sesión con el administrador, se abrirá el panel de usuarios.

> No abras los archivos PHP con doble clic ni con Live Server: PHP necesita Apache/XAMPP. Usa la dirección `http://localhost/...`.

## Modelo de datos
- `roles` 1:N `usuarios`: comprador, vendedor y administrador.
- `usuarios` 1:0..1 `tiendas`: perfil adicional del vendedor.
- `categorias` 1:N `tiendas` y 1:N `productos`.
- `usuarios` 1:N `productos`, `pedidos` y 1:1 `carritos`.
- `productos` 1:N `producto_imagenes`; favoritos y carrito usan tablas intermedias.
- `pedidos` 1:N `pedido_detalles`.

## Verificación de correo

Después de tener creada la base de datos, ejecuta UNA SOLA VEZ `database/migracion_verificacion_correo.sql` desde MySQL Workbench. La migración marca como verificadas las cuentas que ya existían para no bloquearlas; los registros nuevos quedan pendientes hasta validar el código.

En localhost, el código de seis dígitos se muestra en la pantalla de verificación porque todavía no hay un servidor SMTP configurado. Dura 10 minutos, admite cinco intentos y se puede regenerar después de 60 segundos. El código se guarda en la base de datos únicamente como hash.

**Importante:** el modo de código visible solo funciona en `localhost` o `127.0.0.1`. Los registros públicos quedan bloqueados hasta configurar el envío real por SMTP. No publiques esta versión como sistema de registro listo para producción.

## Seguridad implementada
- Contraseñas con `password_hash()` y verificación con `password_verify()`.
- Consultas preparadas PDO y validación de correo/campos obligatorios.
- Sesiones PHP con cookies HttpOnly y regeneración del ID al iniciar sesión.
- Token CSRF en los formularios de registro y login.
- El panel exige sesión y rol administrador.
- Las cuentas comprador y vendedor nuevas deben verificar su correo antes de iniciar sesión.
- No hay credenciales ni usuario administrador predeterminados en el repositorio.
- Los documentos de identidad no se reciben ni almacenan todavía; la verificación de vendedores queda pendiente para una fase posterior.

## Alcance
El registro de compradores y vendedores, el login/logout y el panel de usuarios están conectados al esquema. La publicación de productos, carrito persistente, pedidos y carga privada de documentos todavía requieren sus módulos PHP. Ejecuta las pruebas locales de registro y login antes de desplegar el proyecto.
