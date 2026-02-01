// Real-time clock
function updateClock() {
  const now = new Date();
  const date = now.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const time = now.toLocaleTimeString("en-US", {
    hour12: true,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });

  document.getElementById("current-date").textContent = date;
  document.getElementById("current-time").textContent = time;
}

// Auto-refresh clock every second
setInterval(updateClock, 1000);
updateClock();

// Auto-logout after 30 minutes of inactivity
let idleTime = 0;
const idleInterval = setInterval(timerIncrement, 60000); // 1 minute

function timerIncrement() {
  idleTime++;
  if (idleTime > 30) {
    // 30 minutes
    window.location.href = "logout.php?timeout=1";
  }
}

// Reset idle time on user activity
document.addEventListener("mousemove", resetIdleTime);
document.addEventListener("keypress", resetIdleTime);
document.addEventListener("click", resetIdleTime);

function resetIdleTime() {
  idleTime = 0;
}

// Attendance validation
document.addEventListener("DOMContentLoaded", function () {
  // Check-in/Check-out validation
  const checkInBtn = document.querySelector('button[name="check_in"]');
  const checkOutBtn = document.querySelector('button[name="check_out"]');

  if (checkInBtn) {
    checkInBtn.addEventListener("click", function (e) {
      const now = new Date();
      const hours = now.getHours();
      const day = now.getDay(); // 0 = Sunday, 6 = Saturday

      // Check if Saturday (weekly holiday)
      if (day === 6) {
        if (
          !confirm(
            "Today is Saturday (Weekly Holiday). Are you sure you want to check-in for overtime?",
          )
        ) {
          e.preventDefault();
        }
      }

      // Check if holiday
      const holidayElement = document.querySelector(".holiday-status");
      if (holidayElement) {
        if (
          !confirm(
            "Today is a holiday. This will be counted as overtime. Continue?",
          )
        ) {
          e.preventDefault();
        }
      }
    });
  }

  if (checkOutBtn) {
    checkOutBtn.addEventListener("click", function (e) {
      const checkInTime = document.querySelector('input[name="check_in_time"]');
      if (checkInTime) {
        const checkIn = new Date(`1970-01-01T${checkInTime.value}`);
        const now = new Date();
        const workedHours = (now - checkIn) / (1000 * 60 * 60);

        if (workedHours < 1) {
          if (
            !confirm(
              "You have worked less than 1 hour. This will be marked as Half Day. Continue?",
            )
          ) {
            e.preventDefault();
          }
        }
      }
    });
  }

  // Leave date validation
  const startDate = document.querySelector('input[name="start_date"]');
  const endDate = document.querySelector('input[name="end_date"]');

  if (startDate && endDate) {
    startDate.addEventListener("change", function () {
      endDate.min = this.value;
    });

    endDate.addEventListener("change", function () {
      const start = new Date(startDate.value);
      const end = new Date(this.value);
      const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

      if (diffDays > 30) {
        alert(
          "Leave cannot exceed 30 days. Please contact HR for longer leaves.",
        );
        this.value = "";
      }
    });
  }
});

// Chart.js integration for reports
function initAttendanceChart() {
  const ctx = document.getElementById("attendanceChart");
  if (!ctx) return;

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: ["Present", "Absent", "Leave", "Holiday", "Half Day"],
      datasets: [
        {
          label: "Attendance Summary",
          data: [
            document.querySelector(".present")?.textContent || 0,
            document.querySelector(".absent")?.textContent || 0,
            document.querySelector(".leave")?.textContent || 0,
            document.querySelector(".holiday")?.textContent || 0,
            document.querySelector(".half-day")?.textContent || 0,
          ],
          backgroundColor: [
            "#27ae60",
            "#e74c3c",
            "#f39c12",
            "#3498db",
            "#9b59b6",
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
      },
    },
  });
}

// Initialize chart when page loads
if (typeof Chart !== "undefined") {
  document.addEventListener("DOMContentLoaded", initAttendanceChart);
}
