// methode um benutzer zu aktivieren / deaktivieren
function toggleMfaEnforcement(userId, action) {
  let fetchUrl = action === "enable" ? "/admin/enforceMfa" : "/admin/unenforceMfa";
  const formData = new FormData();
  formData.append("id", userId);

  fetch(fetchUrl, {
    method: "POST",
    headers: { "X-Requested-With": "XMLHttpRequest" },
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        let micon = document.getElementById("enforceStatus" + userId);
        let mmicon = document.getElementById("menforceStatus" + userId);
        let newAction = action === "enable" ? "disable" : "enable";
        let addClasses =
          action === "enable" ? ["text-warning", "bi-check-circle-fill"] : ["text-danger", "bi-x-circle-fill"];
        let removeClasses =
          action === "enable" ? ["text-danger", "bi-x-circle-fill"] : ["text-warning", "bi-check-circle-fill"];

        micon.classList.remove(...removeClasses);
        micon.classList.add(...addClasses);
        micon.setAttribute("onclick", `toggleMfaEnforcement(${userId}, '${newAction}')`);
        mmicon.classList.remove(...removeClasses);
        mmicon.classList.add(...addClasses);
        mmicon.setAttribute("onclick", `toggleMfaEnforcement(${userId}, '${newAction}')`);
        showToast(`2FA successfully ${newAction === "enable" ? "unenforced" : "enforced"}!`, "success", "Success");
      } else {
        alert("Fehler beim Ändern des 2fa-Enforcement-Status.");
      }
    })
    .catch((error) => console.error("Fehler beim Ändern des 2FA-Enforcement-Status:", error));
}

// methode um benutzer zu aktivieren / deaktivieren
function toggleUserAccountStatus(userId, action) {
  let fetchUrl = action === "enable" ? "/admin/enableUser" : "/admin/disableUser";
  const formData = new FormData();
  formData.append("id", userId);

  fetch(fetchUrl, {
    method: "POST",
    headers: { "X-Requested-With": "XMLHttpRequest" },
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        let micon = document.getElementById("accountStatus" + userId);
        let mmicon = document.getElementById("maccountStatus" + userId);
        let newAction = action === "enable" ? "disable" : "enable";
        let addClasses =
          action === "enable" ? ["text-success", "bi-check-circle-fill"] : ["text-danger", "bi-x-circle-fill"];
        let removeClasses =
          action === "enable" ? ["text-danger", "bi-x-circle-fill"] : ["text-success", "bi-check-circle-fill"];

        micon.classList.remove(...removeClasses);
        micon.classList.add(...addClasses);
        micon.setAttribute("onclick", `toggleUserAccountStatus(${userId}, '${newAction}')`);
        mmicon.classList.remove(...removeClasses);
        mmicon.classList.add(...addClasses);
        mmicon.setAttribute("onclick", `toggleUserAccountStatus(${userId}, '${newAction}')`);
        showToast(
          `Useraccount successfully ${newAction === "enable" ? "deactivated" : "activated"}!`,
          "success",
          "Success"
        );
      } else {
        alert("Fehler beim Ändern des Useraccount-Status.");
      }
    })
    .catch((error) => console.error("Fehler beim Ändern des Useraccount-Status:", error));
}

// methode um mfa zu deaktiveren oder zu aktivieren für einen benutzer
function toggleMfa(userId, action) {
  let fetchUrl = action === "enable" ? "/admin/enableMfa" : "/admin/disableMfa";
  const formData = new FormData();
  formData.append("id", userId);

  fetch(fetchUrl, {
    method: "POST",
    headers: { "X-Requested-With": "XMLHttpRequest" },
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        let micon = document.getElementById("mfaEnabled" + userId);
        let mmicon = document.getElementById("mmfaEnabled" + userId);
        let newAction = action === "enable" ? "disable" : "enable";
        let addClasses =
          action === "enable" ? ["text-success", "bi-check-circle-fill"] : ["text-danger", "bi-x-circle-fill"];
        let removeClasses =
          action === "enable" ? ["text-danger", "bi-x-circle-fill"] : ["text-success", "bi-check-circle-fill"];

        micon.classList.remove(...removeClasses);
        micon.classList.add(...addClasses);
        micon.setAttribute("onclick", `toggleMfa(${userId}, '${newAction}')`);
        mmicon.classList.remove(...removeClasses);
        mmicon.classList.add(...addClasses);
        mmicon.setAttribute("onclick", `toggleMfa(${userId}, '${newAction}')`);
        showToast(`2FA successfully ${newAction === "enable" ? "deactivated" : "activated"}!`, "success", "Success");
      } else {
        alert("Fehler beim Ändern des 2FA-Status.");
      }
    })
    .catch((error) => console.error("Fehler beim Ändern des 2fa-Status:", error));
}

// methode um ein ebnutzer array per ajax abzurufen
function fetchUsers(query) {
  loadingIndicator.style.display = "block"; // Ladeindikator anzeigen
  userTableBody.innerHTML = ""; // Tabelle leeren

  fetch(`/admin/users?search=${encodeURIComponent(query)}`, {
    headers: { "X-Requested-With": "XMLHttpRequest" }, // Markiere als AJAX
  })
    .then((response) => response.json())
    .then((data) => {
      userTableBody.innerHTML = "";

      if (data.users.length > 0) {
        data.users.forEach((user) => {
          userTableBody.innerHTML += `
                    <tr>
                        <td>${user.id}</td>
                        <td style="min-width:40px !important; text-align:center;">
                            ${
                              user.username === "super.admin"
                                ? `<i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="Super-Admin-Account kann nicht deaktiviert werden."></i>`
                              : user.status === 1
                                ? `<i id="accountStatus${user.id}" style="cursor:pointer;" onclick="toggleUserAccountStatus(${user.id},'disable');" class="bi-check-circle-fill text-success" data-bs-toggle="tooltip" title="Disable Useraccount."></i>`
                                : `<i id="accountStatus${user.id}" style="cursor:pointer;" onclick="toggleUserAccountStatus(${user.id},'enable');" class="bi-x-circle-fill text-danger" data-bs-toggle="tooltip" title="Enable Useraccount."></i>`
                            }
                        </td>
                        <td style="min-width:40px !important; text-align:center;">
                            ${
                              user.mfaEnabled === 1 && user.mfaSecret !== ""
                                ? `<i id="mfaEnabled${user.id}" style="cursor:pointer;" data-bs-toggle="tooltip" title="Disable 2FA." class="bi-check-circle-fill text-success" onclick="toggleMfa(${user.id},'disable');"></i>`
                                : user.mfaEnabled === 0 && user.mfaSecret !== ""
                                ? `<i id="mfaEnabled${user.id}" style="cursor:pointer;" data-bs-toggle="tooltip" title="Enable 2FA." class="bi-x-circle-fill text-danger" onclick="toggleMfa(${user.id},'enable');"></i>`
                                : `<i id="mfaEnabled${user.id}" class="bi-x-circle-fill text-secondary" data-bs-toggle="popover" title="User has not setup 2fa!"></i>`
                            }
                        </td>
                        <td style="min-width:40px !important; text-align:center;">
                            ${
                              user.username === "super.admin"
                                ? `<i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA enforcement cannot be changed for super.admin."></i>`
                              : user.mfaSecret !== ""
                                ? `<i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA is configured already."></i>`
                                : user.mfaEnforced === 1
                                ? `<i id="enforceStatus${user.id}" style="cursor:pointer;" onclick="toggleMfaEnforcement(${user.id},'disable');" class="bi-check-circle-fill text-warning" data-bs-toggle="tooltip" title="Click to unenforce 2FA for user."></i>`
                                : `<i id="enforceStatus${user.id}" style="cursor:pointer;" onclick="toggleMfaEnforcement(${user.id},'enable');" class="bi-x-circle-fill text-danger" data-bs-toggle="tooltip" title="Click to enforce 2FA for user."></i>`
                            }
                        </td>

                        <td class="text-nowrap">${user.username}</td>
                        <td class="w-100">${user.email}</td>
                        <td class="text-nowrap" style="white-space: nowrap; width: 1%;">
                            ${
                              user.username === "super.admin"
                              ? `<button class="btn btn-sm btn-secondary delete-user-btn" data-user-id="${user.id}" data-bs-toggle="tooltip" title="super.admin cannot be deleted!" disabled><i class="bi-trash-fill"></i></button>`
                              : `<button class="btn btn-sm btn-danger delete-user-btn" data-user-id="${user.id}"><i class="bi-trash-fill"></i></button>`                              
                            }
                            <a href="edit.php?id=${user.id}" class="btn btn-sm btn-warning">
                                <i class="bi-pencil"></i>
                            </a>
                        </td>
                    </tr>`;
        });
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      } else {
        userTableBody.innerHTML = `<tr><td colspan="7" class="text-center">Keine Benutzer gefunden</td></tr>`;
      }
    })
    .catch((error) => console.error("Fehler beim Abrufen der Benutzer:", error))
    .finally(() => {
      loadingIndicator.style.display = "none"; // Ladeindikator ausblenden
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const userTableBody = document.getElementById("userTableBody");
  const mobileCardsBody = document.getElementById("mobile-cards");
  const confirmDeleteModal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const loadingIndicator = document.getElementById("loadingIndicator");
  let timeout = null;
  let deleteUserId = null; // Hier speichern wir die User-ID

  // Event-Listener für das suchfeld
  searchInput.addEventListener("input", function () {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      fetchUsers(searchInput.value);
    }, 500);
  });

  // Event-Listener für den Löschen-Button in der Tabelle
  userTableBody.addEventListener("click", function (event) {
    const deleteBtn = event.target.closest(".delete-user-btn");
    if (deleteBtn) {
      deleteUserId = deleteBtn.dataset.userId; // Speichere die User-ID in der Variablen
      confirmDeleteModal.show();
    }
  });

  // Event-Listener für den Löschen-Button in der card ansicht
  mobileCardsBody.addEventListener("click", function (event) {
    const deleteBtn = event.target.closest(".delete-user-btn");
    if (deleteBtn) {
      deleteUserId = deleteBtn.dataset.userId; // Speichere die User-ID in der Variablen
      confirmDeleteModal.show();
    }
  });

  // Event-Listener für den Löschen button im modalen dialog
  confirmDeleteBtn.addEventListener("click", function () {
    if (!deleteUserId) return; // Falls keine ID vorhanden, breche ab
    const formData = new FormData();
    formData.append("id", deleteUserId); // Standard-Formulardaten

    fetch("/admin/deleteUser", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData, // Verwende die gespeicherte User-ID
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          confirmDeleteModal.hide();
          fetchUsers(""); // Benutzerliste neu laden
        } else {
          alert("Fehler beim Löschen des Benutzers.");
        }
      })
      .catch((error) => console.error("Fehler beim Löschen:", error));
  });
});
