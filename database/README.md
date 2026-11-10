# Base de datos - CompraVenta OMT

## Estado actual del repositorio

La interfaz actual está construida con HTML, CSS y JavaScript. Las páginas de registro e inicio de sesión son formularios visuales: todavía no envían datos a un servidor ni autentican usuarios. El carrito y algunos favoritos se manejan en el navegador, por lo que no son persistentes entre dispositivos ni están vinculados a una cuenta.

Este esquema agrega la estructura de MySQL para dar el siguiente paso sin cambiar el diseño de las páginas.

## Archivos

- `database/compra_venta.sql`: crea la base de datos, tablas, relaciones e información inicial de roles y categorías.

## Cómo instalar en MySQL Workbench

1. Abre MySQL Workbench y conéctate a tu servidor local.
2. Ve a **File > Open SQL Script** y selecciona `database/compra_venta.sql`.
3. Ejecuta todo el script con el botón del rayo.
4. Actualiza **Schemas** y verifica que aparezca `compraventa_omt`.
5. Expande **Tables** para revisar las tablas creadas.

## Tablas y relaciones principales

- `roles` 1:N `usuarios`: cada cuenta tiene un rol.
- `usuarios` 1:0..1 `tiendas`: un vendedor puede tener una tienda.
- `usuarios` 1:N `productos`: un vendedor publica productos.
- `categorias` 1:N `productos`: cada producto pertenece a una categoría.
- `productos` 1:N `producto_imagenes`: un producto puede tener varias imágenes.
- `usuarios` N:M `productos` mediante `favoritos`.
- `usuarios` 1:1 `carritos`; `carritos` N:M `productos` mediante `carrito_detalles`.
- `usuarios` 1:N `pedidos`; cada pedido tiene varios `pedido_detalles`.

## Correspondencia con los formularios actuales

### Comprador
- Nombre y apellido -> `usuarios.nombres`, `usuarios.apellidos`
- Correo -> `usuarios.correo`
- Contraseña -> `usuarios.password_hash` (guardar hash, nunca texto plano)
- Rol -> registro `comprador` en `roles`

### Vendedor
- Nombre completo -> dividir en `usuarios.nombres` y `usuarios.apellidos`
- Documento -> `usuarios.tipo_documento` y `usuarios.numero_documento`
- Correo y celular -> `usuarios.correo`, `usuarios.celular`
- País, departamento, ciudad y dirección -> `tiendas`
- Nombre y descripción de tienda -> `tiendas.nombre_tienda`, `tiendas.descripcion`
- Categoría -> `categorias`; el vendedor selecciona una categoría principal en su formulario, pero los productos también tienen su propia categoría.
- Archivos del documento -> guardar únicamente rutas privadas en `tiendas.documento_frontal_ruta` y `tiendas.documento_reverso_ruta`. No guardar los archivos binarios en MySQL ni exponerlos públicamente.

## Seguridad y siguiente etapa

- La contraseña se procesa en PHP con `password_hash($password, PASSWORD_DEFAULT)`; al iniciar sesión se comprueba con `password_verify($password, $hash)`.
- Usar consultas preparadas con PDO o MySQLi; nunca concatenar datos del formulario en SQL.
- Validar en el servidor correo, campos obligatorios, tipo y tamaño de archivos, rol y precio.
- Las credenciales de MySQL deben permanecer en un archivo de configuración del servidor fuera del directorio público y no subirse a GitHub.
- Usar sesiones PHP para mantener el inicio de sesión; no guardar contraseñas ni tokens de sesión en localStorage.
- El formulario HTML por sí solo no puede conectarse de forma segura a MySQL. Se necesita PHP (o un backend equivalente) y ejecutar el proyecto en un servidor local como XAMPP/WAMP/Laragon. Abrir el HTML con doble clic no ejecuta PHP.

## Alcance de esta entrega

Este cambio crea el esquema y su documentación. No conecta todavía los formularios HTML ni implementa registro/login, publicación de productos o checkout. Eso requiere endpoints PHP y ajustes en los formularios; se debe implementar como siguiente fase y probar localmente.
