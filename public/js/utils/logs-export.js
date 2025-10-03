/**
 * Log Export Handler
 * CSP-compliant export functionality for all log pages
 */
document.addEventListener("DOMContentLoaded", function () {
  const exportBtn = document.getElementById("exportLogsBtn");
  
  if (!exportBtn) {
    return;
  }

  exportBtn.addEventListener("click", function () {
    // Get config from data attributes
    const configDiv = document.getElementById("log-config");
    const logType = configDiv.dataset.type.toUpperCase();
    
    // Get current search value
    const searchInput = document.getElementById("search");
    const searchValue = searchInput ? searchInput.value : "";

    // Build export URL
    let exportUrl = `/admin/logs/export?type=${logType}`;
    
    if (searchValue) {
      exportUrl += `&search=${encodeURIComponent(searchValue)}`;
    }

    // Trigger download
    const link = document.createElement("a");
    link.href = exportUrl;
    link.download = "";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
});