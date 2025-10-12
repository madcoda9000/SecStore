document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("search").addEventListener("input", function () {
    fetchRoles();
  });

  document.getElementById("pageSize").addEventListener("change", function () {
    fetchRoles();
  });

  // =============================================
  // NEU: Event-Delegation für Pagination
  // Ersetzt alle onclick="goToPage(X)" Handler
  // =============================================
  document.getElementById("pagination").addEventListener("click", function (e) {
    e.preventDefault();
    if (e.target.tagName === "A" && e.target.dataset.page) {
      const page = parseInt(e.target.dataset.page);
      if (!isNaN(page)) {
        goToPage(page);
      }
    }
  });
});

// Event-Delegation für Delete-Buttons
document.getElementById("rolesTableBody").addEventListener("click", function(e) {
    if (e.target.classList.contains("delete-role-btn")) {
        const roleId = e.target.dataset.roleId;
        const roleName = e.target.dataset.roleName;
        confirmDelete(roleId, roleName);
    }
});

document.addEventListener("DOMContentLoaded", function() {
    // Bestehende Event-Listener...
    document.getElementById("search").addEventListener("input", function() {
        fetchRoles();
    });
    
    document.getElementById("pageSize").addEventListener("change", function() {
        fetchRoles();
    });

    // Event-Delegation für Pagination (bereits hinzugefügt)
    document.getElementById("pagination").addEventListener("click", function(e) {
        e.preventDefault();
        if (e.target.tagName === 'A' && e.target.dataset.page) {
            const page = parseInt(e.target.dataset.page);
            if (!isNaN(page)) {
                goToPage(page);
            }
        }
    });

    // =============================================
    // NEU: Add Role Button Event-Listener
    // Ersetzt onclick="addRole()" im Modal
    // =============================================
    const addRoleBtn = document.getElementById("addRoleBtn");
    if (addRoleBtn) {
        addRoleBtn.addEventListener("click", function() {
            addRole();
        });
    }
    
    // Event-Delegation für dynamisch erstellte Delete-Buttons
    document.getElementById("rolesTableBody").addEventListener("click", function(e) {
        if (e.target.classList.contains("delete-role-btn")) {
            const roleId = e.target.dataset.roleId;
            const roleName = e.target.dataset.roleName;
            confirmDelete(roleId, roleName);
        }
    });
});

let currentPage = 1;

function fetchRoles() {
  let search = document.getElementById("search").value;
  let pageSize = document.getElementById("pageSize").value;
  let spinner = document.getElementById("loadingSpinner");
  let tableBody = document.getElementById("rolesTableBody");

  spinner.style.display = "block";
  tableBody.innerHTML = "";

  fetch(`/admin/roles?search=${search}&pageSize=${pageSize}&page=${currentPage}`)
    .then((response) => response.json())
    .then((data) => {
      tableBody.innerHTML = "";
      data.roles.forEach((role) => {
        let row = `<tr>
            <td>${role.id}</td>
            <td>${role.roleName}</td>
            <td>
                <button class="btn btn-danger btn-sm delete-role-btn" 
                        data-role-id="${role.id}" 
                        data-role-name="${role.roleName}">${messages.msg1}</button>
            </td>
        </tr>`;
        tableBody.innerHTML += row;
      });
      updatePagination(data.page, data.totalPages);
    })
    .catch((error) => {
      // Handle error silently
    })
    .finally(() => {
      spinner.style.display = "none";
    });
}

function updatePagination(current, total) {
  let pagination = document.getElementById("pagination");
  let paginationInfo = document.getElementById("paginationInfo");

  pagination.innerHTML = "";
  paginationInfo.textContent = `${messages.msg4} ${current} ${messages.msg5} ${total}`;

  let startPage = Math.max(1, current - Math.floor(5 / 2));
  let endPage = Math.min(total, startPage + 5 - 1);

  // Previous button
  if (current > 1) {
    let prevLi = document.createElement("li");
    prevLi.className = "page-item";
    // ❌ ALTE VERSION: prevLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${current - 1})">&laquo;</a>`;
    // ✅ NEUE VERSION: data-attribute statt onclick
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current - 1}">&laquo;</a>`;
    pagination.appendChild(prevLi);
  }

  // First page
  if (startPage > 1) {
    let firstLi = document.createElement("li");
    firstLi.className = "page-item";
    // ❌ ALTE VERSION: firstLi.innerHTML = '<a class="page-link" href="#" onclick="goToPage(1)">1</a>';
    // ✅ NEUE VERSION: data-attribute statt onclick
    firstLi.innerHTML = '<a class="page-link" href="#" data-page="1">1</a>';
    pagination.appendChild(firstLi);

    if (startPage > 2) {
      pagination.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
  }

  // Page numbers
  for (let i = startPage; i <= endPage; i++) {
    let li = document.createElement("li");
    li.className = "page-item " + (i === current ? "active" : "");
    // ❌ ALTE VERSION: li.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>`;
    // ✅ NEUE VERSION: data-attribute statt onclick
    li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
    pagination.appendChild(li);
  }

  // Last page
  if (endPage < total) {
    if (endPage < total - 1) {
      pagination.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    let lastLi = document.createElement("li");
    lastLi.className = "page-item";
    // ❌ ALTE VERSION: lastLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${total})">${total}</a>`;
    // ✅ NEUE VERSION: data-attribute statt onclick
    lastLi.innerHTML = `<a class="page-link" href="#" data-page="${total}">${total}</a>`;
    pagination.appendChild(lastLi);
  }

  // Next button
  if (current < total) {
    let nextLi = document.createElement("li");
    nextLi.className = "page-item";
    // ❌ ALTE VERSION: nextLi.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${current + 1})">&raquo;</a>`;
    // ✅ NEUE VERSION: data-attribute statt onclick
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current + 1}">&raquo;</a>`;
    pagination.appendChild(nextLi);
  }
}

function goToPage(page) {
  currentPage = page;
  fetchRoles();
}

function addRole() {
    let roleName = document.getElementById("newRoleName").value;
    
    fetch("/admin/roles/add", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: new URLSearchParams({
          roleName: roleName,
          csrf_token: document.getElementById("csrf_token")?.value,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success case
            fetchRoles();
            document.getElementById("newRoleName").value = "";
            
            // Modal schließen
            const modalElement = document.getElementById("addRoleModal");
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            } else {
                const newModal = new bootstrap.Modal(modalElement);
                newModal.hide();
            }
            
            // Backdrop cleanup
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);
            
            showToast(data.message || `${messages.msg6} "${roleName}" ${messages.msg7}`, "success", "Success");
        } else {
            // Error case
            showToast(data.message || `${messages.msg6} "${roleName}" ${messages.msg8}`, "error", "Error");
        }
    })
    .catch(error => {
        showToast("An error occurred while adding the role.", "error", "Error");
        console.error('Error:', error);
    });
}
function confirmDelete(id, roleName) {
  fetch(`/admin/roles/checkUsers?role=${roleName}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.inUse) {
        showToast(`${messages.msg6} "${roleName}" ${messages.msg8}`, "warning", "Warning");
      } else {
        document.getElementById("deleteMessage").textContent = `${messages.msg9} "${roleName}" ${messages.msg10}`;

        // =============================================
        // NEU: Event-Handler statt inline onclick
        // =============================================
        const deleteBtn = document.getElementById("deleteConfirmBtn");
        // Entferne alte Event-Listener
        deleteBtn.replaceWith(deleteBtn.cloneNode(true));
        const newDeleteBtn = document.getElementById("deleteConfirmBtn");

        newDeleteBtn.addEventListener("click", function () {
          deleteRole(id);
        });

        new bootstrap.Modal(document.getElementById("confirmDeleteModal")).show();
      }
    });
}

function deleteRole(id) {
    let roleName = document.getElementById("deleteMessage").textContent.match(/"([^"]+)"/)[1];
    
    fetch(`/admin/roles/delete`, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: new URLSearchParams({
          id: id,
          csrf_token: document.getElementById("csrf_token")?.value,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success case
            fetchRoles();
            
            // Modal schließen
            const modalElement = document.getElementById("confirmDeleteModal");
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            } else {
                const newModal = new bootstrap.Modal(modalElement);
                newModal.hide();
            }
            
            // Backdrop cleanup
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);
            
            showToast(data.message || `${messages.msg6} "${roleName}" ${messages.msg11}`, "success", "Success");
        } else {
            // Error case
            showToast(data.message || `Error deleting role "${roleName}"`, "error", "Error");
        }
    })
    .catch(error => {
        showToast("An error occurred while deleting the role.", "error", "Error");
        console.error('Error:', error);
    });
}
