document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("search").addEventListener("input", function () {
    fetchLogs();
  });
  document.getElementById("pageSize").addEventListener("change", function () {
    fetchLogs();
  });
});

let currentPage = 1;

function fetchLogs() {
  let search = document.getElementById("search").value;
  let pageSize = document.getElementById("pageSize").value;
  let spinner = document.getElementById("loadingSpinner");
  let tbody = document.getElementById("logTableBody");
  let cardBody = document.getElementById("logCardsBody");

  // Spinner anzeigen und Inhalte leeren
  spinner.style.display = "block";
  tbody.innerHTML = "";
  cardBody.innerHTML = "";

  fetch(`/admin/logs/fetchMaillogs?search=${search}&pageSize=${pageSize}&page=${currentPage}`)
    .then((response) => response.json())
    .then((data) => {
      tbody.innerHTML = "";
      cardBody.innerHTML = "";

      data.logs.forEach((log) => {
        let row = `<tr>
                    <td>${log.id}</td>
                    <td>${log.type}</td>
                    <td>${log.datum_zeit}</td>
                    <td>${log.user}</td>
                    <td>${log.context}</td>
                    <td>${log.message}</td>
                </tr>`;
        tbody.innerHTML += row;

        let card = `<div class="card mb-2 p-2">
                    <div><strong>#${log.id}</strong></div>
                    <div><strong>Type:</strong> ${log.type}</div>
                    <div><strong>Date:</strong> ${log.datum_zeit}</div>
                    <div><strong>User:</strong> ${log.user}</div>
                    <div><strong>Context:</strong> ${log.context}</div>
                    <div><strong>Message:</strong> ${log.message}</div>
                </div>`;
        cardBody.innerHTML += card;
      });

      updatePagination(data.page, data.totalPages);
    })
    .catch((error) => console.error(messages.msg6, error))
    .finally(() => {
      spinner.style.display = "none";
    });
}

function updatePagination(current, total) {
  let pagination = document.getElementById("pagination");
  let paginationInfo = document.getElementById("paginationInfo");

  pagination.innerHTML = "";
  paginationInfo.textContent = `${messages.msg4} ${current} ${messages.msg5} ${total}`;

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
  fetchLogs();
}
