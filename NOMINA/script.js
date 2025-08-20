document.addEventListener("DOMContentLoaded", () => {
  // Configurar fechas por defecto
  const fechaDesde = document.getElementById("fecha_desde")
  const fechaHasta = document.getElementById("fecha_hasta")

  // Si no hay fechas establecidas, usar el mes actual
  if (!fechaDesde.value) {
    const hoy = new Date()
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
    fechaDesde.value = primerDia.toISOString().split("T")[0]
  }

  if (!fechaHasta.value) {
    const hoy = new Date()
    fechaHasta.value = hoy.toISOString().split("T")[0]
  }

  // Validar que fecha desde no sea mayor que fecha hasta
  fechaDesde.addEventListener("change", () => {
    if (fechaHasta.value && fechaDesde.value > fechaHasta.value) {
      alert("La fecha de inicio no puede ser mayor que la fecha de fin")
      fechaDesde.value = ""
    }
  })

  fechaHasta.addEventListener("change", () => {
    if (fechaDesde.value && fechaHasta.value < fechaDesde.value) {
      alert("La fecha de fin no puede ser menor que la fecha de inicio")
      fechaHasta.value = ""
    }
  })

  // Animación para las tarjetas de resumen
  const cards = document.querySelectorAll(".summary-card")
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = "0"
      card.style.transform = "translateY(20px)"
      card.style.transition = "all 0.5s ease"

      setTimeout(() => {
        card.style.opacity = "1"
        card.style.transform = "translateY(0)"
      }, 100)
    }, index * 100)
  })

  // Resaltar filas de la tabla al hacer hover
  const tableRows = document.querySelectorAll(".data-table tbody tr")
  tableRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      this.style.backgroundColor = "#e6f3ff"
      this.style.transform = "scale(1.01)"
      this.style.transition = "all 0.2s ease"
    })

    row.addEventListener("mouseleave", function () {
      this.style.backgroundColor = ""
      this.style.transform = "scale(1)"
    })
  })

  // Mostrar loading al enviar formulario
  const form = document.querySelector(".filters-form")
  form.addEventListener("submit", () => {
    const submitBtn = form.querySelector(".btn-primary")
    submitBtn.innerHTML = "⏳ Consultando..."
    submitBtn.disabled = true
  })
})

// Función para formatear números
function formatNumber(num) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 2,
  }).format(num)
}

// Función para exportar datos
function exportToExcel() {
  const params = new URLSearchParams(window.location.search)
  window.open("export_excel.php?" + params.toString(), "_blank")
}
