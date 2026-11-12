/* =====================================================
   CARRITO COMPRAVENTA OMT
   Persistencia local: los productos no se borran al cerrar
   la página. Solo se eliminan manualmente o al completar
   una compra real (cuando ese flujo esté implementado).
===================================================== */
(() => {
    'use strict';

    const STORAGE_KEY = 'carrito';

    function leerLista(valor) {
        try {
            const datos = JSON.parse(valor || '[]');
            return Array.isArray(datos) ? datos : [];
        } catch {
            return [];
        }
    }

    function claveProducto(producto) {
        return producto.id || [producto.nombre, producto.precio, producto.imagen].join('|');
    }

    function normalizarProducto(producto) {
        if (!producto || typeof producto !== 'object') return null;

        const nombre = String(producto.nombre || '').trim();
        const precio = producto.precio;
        const imagen = String(producto.imagen || 'img/logo.png');
        const cantidad = Math.floor(Number(producto.cantidad));

        if (!nombre || precio === undefined || precio === null || !Number.isFinite(cantidad) || cantidad < 1) {
            return null;
        }

        return {
            id: claveProducto({ ...producto, nombre, precio, imagen }),
            nombre,
            precio: String(precio),
            imagen: imagenSegura(imagen),
            cantidad
        };
    }

    function imagenSegura(valor) {
        try {
            const url = new URL(valor, window.location.href);
            if (url.protocol === 'http:' || url.protocol === 'https:') {
                return url.href;
            }
        } catch {
            // Se usa la imagen local por defecto si la dirección no es válida.
        }
        return 'img/logo.png';
    }

    function guardar(carrito) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
        actualizarContador();
        window.dispatchEvent(new CustomEvent('carrito:actualizado'));
    }

    function obtener() {
        return leerLista(localStorage.getItem(STORAGE_KEY))
            .map(normalizarProducto)
            .filter(Boolean);
    }

    function migrarCarritoAnterior() {
        const anterior = leerLista(sessionStorage.getItem(STORAGE_KEY));
        if (anterior.length === 0) return;

        const actual = obtener();
        anterior.map(normalizarProducto).filter(Boolean).forEach(producto => {
            const existente = actual.find(item => item.id === producto.id);
            if (existente) {
                existente.cantidad += producto.cantidad;
            } else {
                actual.push(producto);
            }
        });

        localStorage.setItem(STORAGE_KEY, JSON.stringify(actual));
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function cantidadTotal() {
        return obtener().reduce((total, producto) => total + producto.cantidad, 0);
    }

    function actualizarContador() {
        const contador = document.getElementById('cart-count');
        if (contador) contador.textContent = cantidadTotal();
    }

    function numeroPrecio(precio) {
        if (typeof precio === 'number') return Math.max(0, Math.round(precio));
        return Number(String(precio).replace(/[^0-9]/g, '')) || 0;
    }

    function formatoPrecio(valor) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(valor);
    }

    function agregar(producto, cantidad = 1) {
        const nuevo = normalizarProducto({ ...producto, cantidad });
        if (!nuevo) {
            alert('No fue posible agregar este producto. Verifica sus datos.');
            return;
        }

        const carrito = obtener();
        const existente = carrito.find(item => item.id === nuevo.id);

        if (existente) {
            existente.cantidad += nuevo.cantidad;
        } else {
            carrito.push(nuevo);
        }

        guardar(carrito);
        return nuevo;
    }

    function crearBoton(texto, accion, clave, clases, etiqueta) {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = clases;
        boton.textContent = texto;
        boton.dataset.accion = accion;
        boton.dataset.clave = clave;
        if (etiqueta) boton.setAttribute('aria-label', etiqueta);
        return boton;
    }

    function renderizarCarrito() {
        const contenedor = document.getElementById('contenedor-productos');
        if (!contenedor) return;

        const vacio = document.getElementById('carrito-vacio');
        const subtotalTexto = document.getElementById('resumen-subtotal');
        const totalTexto = document.getElementById('resumen-total');
        const carrito = obtener();

        actualizarContador();
        contenedor.replaceChildren();

        if (carrito.length === 0) {
            contenedor.style.display = 'none';
            if (vacio) vacio.style.display = 'block';
            if (subtotalTexto) subtotalTexto.textContent = formatoPrecio(0);
            if (totalTexto) totalTexto.textContent = formatoPrecio(0);
            return;
        }

        contenedor.style.display = 'flex';
        if (vacio) vacio.style.display = 'none';

        let subtotalGeneral = 0;

        carrito.forEach(producto => {
            const precio = numeroPrecio(producto.precio);
            const subtotal = precio * producto.cantidad;
            subtotalGeneral += subtotal;

            const tarjeta = document.createElement('article');
            tarjeta.className = 'bg-white p-3 rounded-4 shadow-sm d-flex align-items-center gap-3 flex-wrap';

            const imagen = document.createElement('img');
            imagen.src = imagenSegura(producto.imagen);
            imagen.alt = producto.nombre;
            imagen.loading = 'lazy';
            imagen.className = 'bg-light rounded-3 p-1';
            imagen.style.width = '80px';
            imagen.style.height = '80px';
            imagen.style.objectFit = 'contain';

            const informacion = document.createElement('div');
            informacion.className = 'flex-grow-1';
            const nombre = document.createElement('h6');
            nombre.className = 'fw-bold mb-1';
            nombre.textContent = producto.nombre;
            const precioUnitario = document.createElement('span');
            precioUnitario.className = 'text-muted small';
            precioUnitario.textContent = formatoPrecio(precio) + ' c/u';
            informacion.append(nombre, precioUnitario);

            const controles = document.createElement('div');
            controles.className = 'd-flex align-items-center gap-2';
            controles.append(
                crearBoton('-', 'restar', producto.id, 'btn btn-sm btn-outline-secondary', 'Restar una unidad'),
                Object.assign(document.createElement('span'), {
                    className: 'fw-bold px-2',
                    textContent: String(producto.cantidad)
                }),
                crearBoton('+', 'sumar', producto.id, 'btn btn-sm btn-outline-secondary', 'Agregar una unidad')
            );

            const acciones = document.createElement('div');
            acciones.className = 'text-end ms-auto';
            const totalProducto = document.createElement('div');
            totalProducto.className = 'fw-bold text-dark';
            totalProducto.textContent = formatoPrecio(subtotal);
            const eliminar = crearBoton('Eliminar', 'eliminar', producto.id, 'btn btn-link text-danger p-0 border-0 small');
            acciones.append(totalProducto, eliminar);

            tarjeta.append(imagen, informacion, controles, acciones);
            contenedor.appendChild(tarjeta);
        });

        if (subtotalTexto) subtotalTexto.textContent = formatoPrecio(subtotalGeneral);
        if (totalTexto) totalTexto.textContent = formatoPrecio(subtotalGeneral);
    }

    function iniciar() {
        migrarCarritoAnterior();
        actualizarContador();

        const botonAgregar = document.getElementById('btn-add-cart');
        if (botonAgregar) {
            const parametros = new URLSearchParams(window.location.search);
            const nombre = parametros.get('nombre');
            const precio = parametros.get('precio');
            const categoria = parametros.get('categoria');
            const imagen = parametros.get('img');
            const descripcion = parametros.get('desc');

            if (nombre) document.getElementById('det-nombre').textContent = nombre;
            if (precio) document.getElementById('det-precio').textContent = precio;
            if (categoria) {
                document.getElementById('det-categoria').textContent = categoria.toUpperCase();
                document.getElementById('det-categoria-top').textContent = categoria;
            }
            if (imagen) document.getElementById('det-imagen').src = imagenSegura(imagen);
            if (descripcion) document.getElementById('det-descripcion').textContent = descripcion;

            const cantidadInput = document.getElementById('input-cantidad');
            document.getElementById('btn-menos')?.addEventListener('click', () => {
                cantidadInput.value = Math.max(1, Number(cantidadInput.value) - 1);
            });
            document.getElementById('btn-mas')?.addEventListener('click', () => {
                cantidadInput.value = Math.min(99, Number(cantidadInput.value) + 1);
            });

            botonAgregar.addEventListener('click', () => {
                const producto = agregar({
                    nombre: document.getElementById('det-nombre').textContent,
                    precio: document.getElementById('det-precio').textContent,
                    imagen: document.getElementById('det-imagen').getAttribute('src')
                }, Number(cantidadInput.value));

                if (producto) {
                    alert('¡Se agregó ' + producto.cantidad + ' unidad(es) de "' + producto.nombre + '" al carrito!');
                }
            });
        }

        renderizarCarrito();

        const contenedor = document.getElementById('contenedor-productos');
        contenedor?.addEventListener('click', evento => {
            const boton = evento.target.closest('button[data-accion]');
            if (!boton) return;

            const carrito = obtener();
            const indice = carrito.findIndex(item => item.id === boton.dataset.clave);
            if (indice < 0) return;

            switch (boton.dataset.accion) {
                case 'sumar':
                    carrito[indice].cantidad += 1;
                    break;
                case 'restar':
                    carrito[indice].cantidad -= 1;
                    if (carrito[indice].cantidad <= 0) carrito.splice(indice, 1);
                    break;
                case 'eliminar':
                    carrito.splice(indice, 1);
                    break;
                default:
                    return;
            }

            guardar(carrito);
            renderizarCarrito();
        });

        document.getElementById('btn-vaciar-carrito')?.addEventListener('click', () => {
            if (obtener().length === 0) return;
            if (confirm('¿Estás seguro de que deseas vaciar tu carrito?')) {
                guardar([]);
                renderizarCarrito();
            }
        });

        document.getElementById('btn-finalizar-compra')?.addEventListener('click', () => {
            alert('Tu carrito está guardado. El proceso de pago todavía no está habilitado en esta versión.');
        });

        window.addEventListener('storage', evento => {
            if (evento.key === STORAGE_KEY) renderizarCarrito();
        });
        window.addEventListener('carrito:actualizado', renderizarCarrito);
    }

    window.CarritoOMT = { obtener, agregar, guardar, actualizarContador, formatoPrecio };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
