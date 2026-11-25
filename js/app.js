// ===============================
// APP.JS - COMPRA VENTA
// ===============================

// ===============================
// CARRUSEL HERO ANIMADO
// ===============================
(() => {
  const hero = document.querySelector(".hero-section");
  if (!hero) return;

  const slides = [
    {
      image: "img/almacen_confianza.jpg",
      kicker: "COMPRA • VENDE • DESCUBRE",
      title: "Todo lo que buscas,",
      highlight: "en un solo lugar.",
      description: "Encuentra productos para tu día a día, descubre buenas oportunidades y compra de forma fácil en CompraVenta OMT."
    },
    {
      image: "img/garantia_confianza.jpg",
      kicker: "TU PRÓXIMO HALLAZGO TE ESPERA",
      title: "Buenas oportunidades",
      highlight: "a un clic de distancia.",
      description: "Explora diferentes productos, compara lo que te gusta y encuentra eso que estabas buscando."
    },
    {
      image: "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1800&q=85",
      kicker: "HAZ CRECER TUS IDEAS",
      title: "Vende lo que tienes,",
      highlight: "llega a más personas.",
      description: "Publica tus artículos y conecta con compradores que buscan nuevas opciones."
    }
  ];

  const dots = Array.from(hero.querySelectorAll(".slider-dots .dot"));
  const title = hero.querySelector(".hero-title");
  const highlight = hero.querySelector("[data-hero-highlight]");
  const kicker = hero.querySelector("[data-hero-kicker]");
  const description = hero.querySelector("[data-hero-description]");
  const counter = hero.querySelector("[data-hero-current]");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let current = 0;
  let timer = null;

  function showSlide(index) {
    current = (index + slides.length) % slides.length;
    const slide = slides[current];

    hero.classList.add("is-changing");
    hero.style.setProperty("--hero-image", `url("${slide.image}")`);
    title.firstChild.textContent = slide.title;
    highlight.textContent = slide.highlight;
    kicker.textContent = slide.kicker;
    description.textContent = slide.description;
    if (counter) counter.textContent = String(current + 1).padStart(2, "0");

    dots.forEach((dot, i) => {
      dot.classList.toggle("active", i === current);
      dot.setAttribute("aria-pressed", i === current ? "true" : "false");
    });

    window.setTimeout(() => hero.classList.remove("is-changing"), reduceMotion ? 0 : 650);
  }

  function stopAutoplay() {
    if (timer) window.clearInterval(timer);
    timer = null;
  }

  function startAutoplay() {
    stopAutoplay();
    if (!reduceMotion && !document.hidden) {
      timer = window.setInterval(() => showSlide(current + 1), 5500);
    }
  }

  hero.querySelector(".hero-control.right")?.addEventListener("click", () => {
    showSlide(current + 1);
    startAutoplay();
  });

  hero.querySelector(".hero-control.left")?.addEventListener("click", () => {
    showSlide(current - 1);
    startAutoplay();
  });

  dots.forEach((dot, index) => dot.addEventListener("click", () => {
    showSlide(index);
    startAutoplay();
  }));

  hero.addEventListener("mouseenter", stopAutoplay);
  hero.addEventListener("mouseleave", startAutoplay);
  hero.addEventListener("focusin", stopAutoplay);
  hero.addEventListener("focusout", startAutoplay);
  document.addEventListener("visibilitychange", () => document.hidden ? stopAutoplay() : startAutoplay());

  showSlide(0);
  startAutoplay();
})();

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
    if (window.OMTNotificar) {
      window.OMTNotificar(button.classList.contains("liked") ? "Producto guardado en favoritos." : "Producto eliminado de favoritos.", "info");
    }
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