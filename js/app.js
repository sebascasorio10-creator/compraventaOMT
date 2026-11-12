// ===============================
// APP.JS - COMPRA VENTA
// ===============================

// ===============================
// CARRUSEL HERO
// ===============================

// Usamos imágenes válidas para que no se ponga en negro al pasar el tiempo
const heroImages = [
  "img/almacen_confianza.jpg",
  "img/garantia_confianza.jpg",
  "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80"
];

let currentSlide = 0;

const heroSection = document.querySelector(".hero-section");
const dots = document.querySelectorAll(".slider-dots .dot, .slider-dots span");

function changeSlide(index) {
  currentSlide = index;

  if (heroSection) {
    heroSection.style.backgroundImage = `url('${heroImages[currentSlide]}')`;
  }

  dots.forEach(dot => {
    dot.classList.remove("active");
  });

  if (dots[currentSlide]) {
    dots[currentSlide].classList.add("active");
  }
}

// Botón siguiente
const nextButton = document.querySelector(".hero-control.right");
if (nextButton) {
  nextButton.addEventListener("click", () => {
    currentSlide++;
    if (currentSlide >= heroImages.length) {
      currentSlide = 0;
    }
    changeSlide(currentSlide);
  });
}

// Botón anterior
const prevButton = document.querySelector(".hero-control.left");
if (prevButton) {
  prevButton.addEventListener("click", () => {
    currentSlide--;
    if (currentSlide < 0) {
      currentSlide = heroImages.length - 1;
    }
    changeSlide(currentSlide);
  });
}

// Puntos del slider
dots.forEach((dot, index) => {
  dot.addEventListener("click", () => {
    changeSlide(index);
  });
});

// Cambio automático cada 5 segundos
setInterval(() => {
  currentSlide++;
  if (currentSlide >= heroImages.length) {
    currentSlide = 0;
  }
  changeSlide(currentSlide);
}, 5000);

// ===============================
// FAVORITOS PRODUCTOS
// ===============================

const favorites = document.querySelectorAll(".favorite");

favorites.forEach(button => {
  button.addEventListener("click", () => {
    const icon = button.querySelector("i");
    if (icon) {
      icon.classList.toggle("bi-heart");
      icon.classList.toggle("bi-heart-fill");
    }
    button.classList.toggle("liked");
  });
});

// ===============================
// BUSCADOR EN TIEMPO REAL
// ===============================

const buscador = document.getElementById("buscador");
const productos = document.querySelectorAll(".producto");
const sinResultados = document.getElementById("sinResultados");

if (buscador) {
  buscador.addEventListener("input", function () {
    const texto = this.value.toLowerCase().trim();
    let encontrados = 0;

    productos.forEach(producto => {
      const nombre = producto.dataset.nombre ? producto.dataset.nombre.toLowerCase() : "";
      const contenedorPadre = producto.parentElement;

      if (nombre.includes(texto)) {
        if (contenedorPadre) contenedorPadre.style.display = "";
        encontrados++;
      } else {
        if (contenedorPadre) contenedorPadre.style.display = "none";
      }
    });

    if (sinResultados) {
      if (encontrados === 0 && texto !== "") {
        sinResultados.style.display = "block";
      } else {
        sinResultados.style.display = "none";
      }
    }
  });
}

// ===============================
// ANIMACIÓN SCROLL
// ===============================

const cards = document.querySelectorAll(".product-card");

if (cards.length > 0) {
  const observer = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }
      });
    },
    { threshold: 0.1 }
  );

  cards.forEach(card => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = "all .6s ease";
    observer.observe(card);
  });
}

// ===============================
// AÑO AUTOMÁTICO FOOTER
// ===============================

const year = new Date().getFullYear();
const footerText = document.querySelector(".footer-bottom p");

if (footerText) {
  footerText.innerHTML = `© ${year} COMPRAVENTA. Todos los derechos reservados por OMT.`;
}



// ===============================
// ACTUALIZAR CONTADOR DE CARRITO EN INDEX
// ===============================
function actualizarContadorIndex() {
  const cartCount = document.getElementById('cart-count');
  if (cartCount) {
    let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
    let totalItems = carrito.reduce((sum, item) => sum + item.cantidad, 0);
    cartCount.textContent = totalItems;
  }
}

// Ejecutar al cargar la página
document.addEventListener('DOMContentLoaded', actualizarContadorIndex);