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
