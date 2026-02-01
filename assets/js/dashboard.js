/**
 * Dashboard-specific JavaScript functions
 */

document.addEventListener("DOMContentLoaded", function () {
  // Initialize all dashboard components
  initDashboard();
  initCharts();
  initNotifications();
  initRealTimeUpdates();
});

/**
 * Initialize dashboard
 */
function initDashboard() {
  console.log("Dashboard initialized");

  // Auto-refresh dashboard every 5 minutes
  setInterval(() => {
    refreshDashboardStats();
  }, 300000); // 5 minutes

  // Initialize tooltips
  initTooltips();

  // Initialize date pickers
  initDatePickers();

  // Initialize export buttons
  initExportButtons();
}

/**
 * Initialize charts
 */
function initCharts() {
  // Attendance chart
  const attendanceCtx = document.getElementById("attendanceChart");
  if (attendanceCtx) {
    const attendanceChart = new Chart(attendanceCtx, {
      type: "bar",
      data: {
        labels: ["Present", "Absent", "Leave", "Holiday", "Half Day"],
        datasets: [
          {
            label: "Attendance Distribution",
            data: [
              document.querySelector(".present-count")?.textContent || 0,
              document.querySelector(".absent-count")?.textContent || 0,
              document.querySelector(".leave-count")?.textContent || 0,
              document.querySelector(".holiday-count")?.textContent || 0,
              document.querySelector(".halfday-count")?.textContent || 0,
            ],
            backgroundColor: [
              "rgba(39, 174, 96, 0.7)",
              "rgba(231, 76, 60, 0.7)",
              "rgba(243, 156, 18, 0.7)",
              "rgba(52, 152, 219, 0.7)",
              "rgba(155, 89, 182, 0.7)",
            ],
            borderColor: [
              "rgb(39, 174, 96)",
              "rgb(231, 76, 60)",
              "rgb(243, 156, 18)",
              "rgb(52, 152, 219)",
              "rgb(155, 89, 182)",
            ],
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "top",
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `${context.dataset.label}: ${context.parsed.y}`;
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Number of Days",
            },
          },
        },
      },
    });
  }

  // Overtime chart
  const overtimeCtx = document.getElementById("overtimeChart");
  if (overtimeCtx) {
    const overtimeChart = new Chart(overtimeCtx, {
      type: "line",
      data: {
        labels: [
          "Jan",
          "Feb",
          "Mar",
          "Apr",
          "May",
          "Jun",
          "Jul",
          "Aug",
          "Sep",
          "Oct",
          "Nov",
          "Dec",
        ],
        datasets: [
          {
            label: "Overtime Hours",
            data: Array.from({ length: 12 }, () =>
              Math.floor(Math.random() * 100),
            ),
            borderColor: "rgb(255, 99, 132)",
            backgroundColor: "rgba(255, 99, 132, 0.2)",
            tension: 0.3,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "top",
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Hours",
            },
          },
        },
      },
    });
  }

  // Department distribution chart
  const deptCtx = document.getElementById("departmentChart");
  if (deptCtx) {
    const deptChart = new Chart(deptCtx, {
      type: "doughnut",
      data: {
        labels: ["IT", "HR", "Finance", "Marketing", "Sales", "Admin"],
        datasets: [
          {
            label: "Employees by Department",
            data: [15, 8, 12, 10, 20, 5],
            backgroundColor: [
              "rgba(255, 99, 132, 0.8)",
              "rgba(54, 162, 235, 0.8)",
              "rgba(255, 206, 86, 0.8)",
              "rgba(75, 192, 192, 0.8)",
              "rgba(153, 102, 255, 0.8)",
              "rgba(255, 159, 64, 0.8)",
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
              "rgba(75, 192, 192, 1)",
              "rgba(153, 102, 255, 1)",
              "rgba(255, 159, 64, 1)",
            ],
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "right",
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const label = context.label || "";
                const value = context.parsed || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              },
            },
          },
        },
      },
    });
  }
}

/**
 * Initialize notifications
 */
function initNotifications() {
  // Check for new notifications every minute
  setInterval(() => {
    checkNewNotifications();
  }, 60000); // 1 minute

  // Mark notification as read when clicked
  document.querySelectorAll(".notification-item").forEach((item) => {
    item.addEventListener("click", function () {
      const notificationId = this.dataset.id;
      markNotificationAsRead(notificationId);
    });
  });

  // Show notification count in title
  updateNotificationBadge();
}

/**
 * Check for new notifications
 */
function checkNewNotifications() {
  fetch("api/notifications.php?action=check")
    .then((response) => response.json())
    .then((data) => {
      if (data.count > 0) {
        showNotificationAlert(data.count);
        updateNotificationBadge(data.count);
      }
    })
    .catch((error) => console.error("Error checking notifications:", error));
}

/**
 * Show notification alert
 */
function showNotificationAlert(count) {
  // Check if user is active on page
  if (document.hidden) return;

  const alert = document.createElement("div");
  alert.className = "notification-alert";
  alert.innerHTML = `
        <div class="alert-content">
            <strong>${count} new notification${count > 1 ? "s" : ""}</strong>
            <button onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;

  document.body.appendChild(alert);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (alert.parentNode) {
      alert.remove();
    }
  }, 5000);
}

/**
 * Update notification badge
 */
function updateNotificationBadge(count = null) {
  const badge = document.querySelector(".notification-badge");
  if (!badge) return;

  if (count === null) {
    // Get current count
    fetch("api/notifications.php?action=count")
      .then((response) => response.json())
      .then((data) => {
        badge.textContent = data.count > 99 ? "99+" : data.count;
        badge.style.display = data.count > 0 ? "block" : "none";
      });
  } else {
    badge.textContent = count > 99 ? "99+" : count;
    badge.style.display = count > 0 ? "block" : "none";
  }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead(notificationId) {
  fetch("api/notifications.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "mark_read",
      id: notificationId,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Remove notification from UI
        const notification = document.querySelector(
          `[data-id="${notificationId}"]`,
        );
        if (notification) {
          notification.remove();
        }
        updateNotificationBadge();
      }
    });
}

/**
 * Initialize real-time updates
 */
function initRealTimeUpdates() {
  // Update clock every second
  updateRealTimeClock();
  setInterval(updateRealTimeClock, 1000);

  // Update attendance status every 30 seconds
  setInterval(updateAttendanceStatus, 30000);

  // Update dashboard stats every minute
  setInterval(updateDashboardStats, 60000);
}

/**
 * Update real-time clock
 */
function updateRealTimeClock() {
  const now = new Date();
  const options = {
    timeZone: "Asia/Kathmandu",
    hour12: true,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  };

  const time = now.toLocaleTimeString("en-US", options);
  const date = now.toLocaleDateString("en-US", {
    timeZone: "Asia/Kathmandu",
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  const clockElements = document.querySelectorAll(".real-time-clock");
  clockElements.forEach((element) => {
    element.innerHTML = `<span>${date} • ${time} (NPT)</span>`;
  });
}

/**
 * Update attendance status
 */
function updateAttendanceStatus() {
  // Only update if on employee dashboard
  if (!window.location.pathname.includes("employee/dashboard")) return;

  fetch("api/attendance_status.php")
    .then((response) => response.json())
    .then((data) => {
      updateAttendanceUI(data);
    })
    .catch((error) => console.error("Error updating attendance:", error));
}

/**
 * Update attendance UI
 */
function updateAttendanceUI(data) {
  // Update check-in/check-out buttons
  const checkInBtn = document.querySelector('[name="check_in"]');
  const checkOutBtn = document.querySelector('[name="check_out"]');
  const statusElement = document.querySelector(".attendance-status");

  if (checkInBtn && data.can_check_in) {
    checkInBtn.disabled = false;
    checkInBtn.innerHTML = `Check In (${new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })})`;
  } else if (checkInBtn) {
    checkInBtn.disabled = true;
  }

  if (checkOutBtn && data.can_check_out) {
    checkOutBtn.disabled = false;
    checkOutBtn.innerHTML = `Check Out (${new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })})`;
  } else if (checkOutBtn) {
    checkOutBtn.disabled = true;
  }

  if (statusElement && data.status) {
    statusElement.textContent = data.status;
    statusElement.className = `status-badge ${data.status.toLowerCase()}`;
  }
}

/**
 * Refresh dashboard stats
 */
function refreshDashboardStats() {
  fetch("api/dashboard_stats.php")
    .then((response) => response.json())
    .then((data) => {
      updateStatsUI(data);
    })
    .catch((error) => console.error("Error refreshing stats:", error));
}

/**
 * Update stats UI
 */
function updateStatsUI(data) {
  // Update total employees
  const totalEmp = document.querySelector(".total-employees");
  if (totalEmp) totalEmp.textContent = data.total_employees;

  // Update present today
  const presentToday = document.querySelector(".present-today");
  if (presentToday) presentToday.textContent = data.present_today;

  // Update absent today
  const absentToday = document.querySelector(".absent-today");
  if (absentToday) absentToday.textContent = data.absent_today;

  // Update pending leaves
  const pendingLeaves = document.querySelector(".pending-leaves");
  if (pendingLeaves) pendingLeaves.textContent = data.pending_leaves;
}

/**
 * Initialize tooltips
 */
function initTooltips() {
  const tooltips = document.querySelectorAll("[data-tooltip]");

  tooltips.forEach((element) => {
    element.addEventListener("mouseenter", function (e) {
      const tooltip = document.createElement("div");
      tooltip.className = "custom-tooltip";
      tooltip.textContent = this.dataset.tooltip;
      document.body.appendChild(tooltip);

      const rect = this.getBoundingClientRect();
      tooltip.style.left = rect.left + window.scrollX + "px";
      tooltip.style.top =
        rect.top + window.scrollY - tooltip.offsetHeight - 10 + "px";

      this._tooltip = tooltip;
    });

    element.addEventListener("mouseleave", function () {
      if (this._tooltip) {
        this._tooltip.remove();
        this._tooltip = null;
      }
    });
  });
}

/**
 * Initialize date pickers
 */
function initDatePickers() {
  const dateInputs = document.querySelectorAll('input[type="date"]');

  dateInputs.forEach((input) => {
    // Set min date to today for future dates
    if (input.name.includes("start") || input.name.includes("date")) {
      input.min = new Date().toISOString().split("T")[0];
    }

    // Add date format helper
    input.addEventListener("focus", function () {
      this.type = "text";
      this.placeholder = "YYYY-MM-DD";
    });

    input.addEventListener("blur", function () {
      this.type = "date";
    });
  });
}

/**
 * Initialize export buttons
 */
function initExportButtons() {
  const exportButtons = document.querySelectorAll("[data-export]");

  exportButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const type = this.dataset.export;
      const date = this.dataset.date || new Date().toISOString().split("T")[0];

      showExportDialog(type, date);
    });
  });
}

/**
 * Show export dialog
 */
function showExportDialog(type, date) {
  const dialog = document.createElement("div");
  dialog.className = "export-dialog";
  dialog.innerHTML = `
        <div class="dialog-content">
            <h3>Export ${type.toUpperCase()} Report</h3>
            <p>Select format and date range:</p>
            
            <div class="form-group">
                <label>Format:</label>
                <select id="exportFormat">
                    <option value="csv">CSV (Excel)</option>
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel (.xlsx)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>From Date:</label>
                <input type="date" id="exportFrom" value="${date}">
            </div>
            
            <div class="form-group">
                <label>To Date:</label>
                <input type="date" id="exportTo" value="${date}">
            </div>
            
            <div class="dialog-actions">
                <button class="btn btn-secondary" onclick="this.closest('.export-dialog').remove()">Cancel</button>
                <button class="btn btn-primary" onclick="processExport('${type}')">Export</button>
            </div>
        </div>
    `;

  document.body.appendChild(dialog);
}

/**
 * Process export
 */
function processExport(type) {
  const format = document.getElementById("exportFormat").value;
  const fromDate = document.getElementById("exportFrom").value;
  const toDate = document.getElementById("exportTo").value;

  // Validate dates
  if (new Date(toDate) < new Date(fromDate)) {
    alert("To date cannot be before from date");
    return;
  }

  // Construct export URL
  let url = `export.php?type=${type}&format=${format}&from=${fromDate}&to=${toDate}`;

  // Add CSRF token if available
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrfToken) {
    url += `&csrf_token=${csrfToken}`;
  }

  // Trigger download
  window.location.href = url;

  // Remove dialog
  document.querySelector(".export-dialog")?.remove();
}

/**
 * Show loading indicator
 */
function showLoading(message = "Loading...") {
  const loading = document.createElement("div");
  loading.className = "loading-overlay";
  loading.innerHTML = `
        <div class="loading-content">
            <div class="spinner"></div>
            <p>${message}</p>
        </div>
    `;
  document.body.appendChild(loading);
  return loading;
}

/**
 * Hide loading indicator
 */
function hideLoading(loadingElement) {
  if (loadingElement && loadingElement.parentNode) {
    loadingElement.parentNode.removeChild(loadingElement);
  }
}

/**
 * Show success message
 */
function showSuccess(message) {
  const messageDiv = document.createElement("div");
  messageDiv.className = "alert alert-success";
  messageDiv.innerHTML = `
        <span>✓ ${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
  messageDiv.style.position = "fixed";
  messageDiv.style.top = "20px";
  messageDiv.style.right = "20px";
  messageDiv.style.zIndex = "1000";

  document.body.appendChild(messageDiv);

  setTimeout(() => {
    if (messageDiv.parentNode) {
      messageDiv.remove();
    }
  }, 5000);
}

/**
 * Show error message
 */
function showError(message) {
  const messageDiv = document.createElement("div");
  messageDiv.className = "alert alert-error";
  messageDiv.innerHTML = `
        <span>✗ ${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
  messageDiv.style.position = "fixed";
  messageDiv.style.top = "20px";
  messageDiv.style.right = "20px";
  messageDiv.style.zIndex = "1000";

  document.body.appendChild(messageDiv);

  setTimeout(() => {
    if (messageDiv.parentNode) {
      messageDiv.remove();
    }
  }, 5000);
}

/**
 * Confirm action
 */
function confirmAction(message, callback) {
  const confirmDiv = document.createElement("div");
  confirmDiv.className = "confirm-dialog";
  confirmDiv.innerHTML = `
        <div class="confirm-content">
            <p>${message}</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary" onclick="this.closest('.confirm-dialog').remove()">Cancel</button>
                <button class="btn btn-danger" onclick="
                    this.closest('.confirm-dialog').remove();
                    ${callback.toString().replace("function", "")}
                ">Confirm</button>
            </div>
        </div>
    `;

  document.body.appendChild(confirmDiv);
}

// Make functions available globally
window.showSuccess = showSuccess;
window.showError = showError;
window.confirmAction = confirmAction;
window.processExport = processExport;
