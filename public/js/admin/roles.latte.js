document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("search").addEventListener("input", function () {
    fetchRoles();
  });
  document.getElementById("pageSize").addEventListener("change", function () {
    fetchRoles();
  });
});

let currentPage = 1;

function fetchRoles() {
  let search = document.getElementById("search").value;
  let pageSize = document.getElementById("pageSize").value;
  let spinner = document.getElementById("loadingSpinner");
  let tbody = document.getElementById("rolesTableBody");

  // Spinner anzeigen und Tabelle leeren
  spinner.style.display = "block";
  tbody.innerHTML = "";

  fetch(`/admin/roles?search=${search}&pageSize=${pageSize}&page=${currentPage}`)
    .then((response) => response.json())
    .then((data) => {
      tbody.innerHTML = "";
      data.roles.forEach((role) => {
        let row = `<tr>
                    <td>${role.id}</td>
                    <td>${role.roleName}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(${role.id}, '${role.roleName}')">Löschen</button>
                    </td>
                </tr>`;
        tbody.innerHTML += row;
      });

      updatePagination(data.page, data.totalPages);
    })
    .catch((error) => console.error("Fehler beim Laden der Rollen:", error))
    .finally(() => {
      // Spinner ausblenden
      spinner.style.display = "none";
    });
}

function updatePagination(current, total) {
  let pagination = document.getElementById("pagination");
  let paginationInfo = document.getElementById("paginationInfo");

  pagination.innerHTML = "";
  paginationInfo.textContent = `Seite ${current} von ${total}`;

  let maxVisiblePages = 5; // Anzahl der sichtbaren Seitenlinks
  let startPage = Math.max(1, current - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(total, startPage + maxVisiblePages - 1);

  // "Zurück"-Button
  if (current > 1) {
    let prevLi = document.createElement("li");
    prevLi.className = "page-item";
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${current - 1})">&laquo;</a>`;
    pagination.appendChild(prevLi);
  }

  // Erste Seite anzeigen, wenn nötig
  if (startPage > 1) {
    let firstLi = document.createElement("li");
    firstLi.className = "page-item";
    firstLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(1)">1</a>`;
    pagination.appendChild(firstLi);
    if (startPage > 2) {
      pagination.innerHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
  }

  // Dynamische Seitenzahlen
  for (let i = startPage; i <= endPage; i++) {
    let li = document.createElement("li");
    li.className = `page-item ${i === current ? "active" : ""}`;
    li.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>`;
    pagination.appendChild(li);
  }

  // Letzte Seite anzeigen, wenn nötig
  if (endPage < total) {
    if (endPage < total - 1) {
      pagination.innerHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    let lastLi = document.createElement("li");
    lastLi.className = "page-item";
    lastLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${total})">${total}</a>`;
    pagination.appendChild(lastLi);
  }

  // "Weiter"-Button
  if (current < total) {
    let nextLi = document.createElement("li");
    nextLi.className = "page-item";
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${current + 1})">&raquo;</a>`;
    pagination.appendChild(nextLi);
  }
}

function goToPage(page) {
  currentPage = page;
  fetchRoles();
}

function addRole() {
  let roleName = document.getElementById("newRoleName").value;
  let formData = new FormData();
  formData.append("roleName", roleName);
  fetch("/admin/roles/add", {
    method: "POST",
    body: formData,
  }).then(() => {
    fetchRoles();
    document.getElementById("newRoleName").value = "";
    bootstrap.Modal.getInstance(document.getElementById("addRoleModal")).hide();
    showToast(`Rolle "${roleName}" erfolgreich erstellt!`, "success", "Success");
  });
}

function confirmDelete(id, roleName) {
  fetch(`/admin/roles/checkUsers?role=${roleName}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.inUse) {
        showToast(
          `Rolle "${roleName}" kann nicht gelöscht werden, da sie noch Benutzern zugewiesen ist!`,
          "warning",
          "Warning"
        );
      } else {
        document.getElementById("deleteMessage").textContent = `Soll die Rolle "${roleName}" wirklich gelöscht werden?`;
        document.getElementById("deleteConfirmBtn").onclick = function () {
          deleteRole(id);
        };
        new bootstrap.Modal(document.getElementById("confirmDeleteModal")).show();
      }
    });
}

function deleteRole(id) {
  let roleName = document.getElementById("deleteMessage").textContent.match(/"([^"]+)"/)[1]; // Holt den Rollennamen aus der Modalmeldung
  let formData = new FormData();
  formData.append("roleId", id);
  fetch(`/admin/roles/delete`, {
    method: "POST",
    body: formData,
  }).then(() => {
    fetchRoles();
    bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide();
    showToast(`Rolle "${roleName}" erfolgreich gelöscht!`, "success", "Success");
  });
}
