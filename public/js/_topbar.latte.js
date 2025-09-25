document.addEventListener("DOMContentLoaded", function () {
  let currentUrl = window.location.pathname;

  document.querySelectorAll(".navbar-nav .nav-link, .dropdown-menu .dropdown-item").forEach((link) => {
    let linkPath = new URL(link.href, window.location.origin).pathname;

    // Ignoriere href="#" oder leere hrefs
    if (linkPath === "#" || linkPath === "") return;

    if (linkPath === currentUrl) {
      link.classList.add("active");

      // Nur Dropdown-Button aktivieren, wenn mind. ein Unterpunkt aktiv ist
      let dropdownMenu = link.closest(".dropdown-menu");
      if (dropdownMenu) {
        let dropdown = dropdownMenu.closest(".dropdown");
        if (dropdown) {
          dropdown.classList.add("has-active");
        }
      }
    }
  });

  // Entferne fälschlicherweise aktive Dropdowns
  document.querySelectorAll(".dropdown").forEach((dropdown) => {
    let dropdownToggle = dropdown.querySelector(".dropdown-toggle");

    if (dropdown.classList.contains("has-active")) {
      dropdownToggle.classList.add("active");
    } else {
      dropdownToggle.classList.remove("active");
    }
  });
});

// Delegierter Handler: reagiert auf Klicks auf Links im Offcanvas
(function () {
  const offEl = document.getElementById('navbarOffcanvas');
  if (!offEl) return;

  offEl.addEventListener('click', function (e) {
    const link = e.target.closest('a.nav-link[href]');
    if (!link) return;

    const href = link.getAttribute('href');
    // Ignoriere hash-links oder javascript:void(0)
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    // Verhindere sofortige Navigation, schließe Offcanvas und navigiere nach dem Schließen
    e.preventDefault();

    const bsOff = bootstrap.Offcanvas.getInstance(offEl) || new bootstrap.Offcanvas(offEl);

    const onHidden = function () {
      offEl.removeEventListener('hidden.bs.offcanvas', onHidden);
      // echte Navigation
      window.location.href = href;
    };

    offEl.addEventListener('hidden.bs.offcanvas', onHidden);
    bsOff.hide();
  }, false);
})();
