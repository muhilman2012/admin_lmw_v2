// Konfigurasi locale & timezone
const LOCALE = "id-ID";
const TIMEZONE = "Asia/Jakarta";

// Format tanggal ke "DD-MM-YYYY"
function formatDateHeader(dateStr) {
  const d = new Date(dateStr + "T00:00:00Z"); // anggap dateStr format "YYYY-MM-DD"
  return new Intl.DateTimeFormat(LOCALE, {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: TIMEZONE,
  })
    .format(d)
    .replaceAll("/", "-"); // ganti slash ke "-"
}

// Format timestamp jadi "DD-MM-YYYY HH:mm:ss"
function formatTimestamp(timestamp) {
  const d = new Date(timestamp);
  const date = new Intl.DateTimeFormat(LOCALE, {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: TIMEZONE,
  })
    .format(d)
    .replaceAll("/", "-");
  const time = new Intl.DateTimeFormat(LOCALE, {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
    timeZone: TIMEZONE,
  })
    .format(d)
    .replaceAll(".", ":");
  return `${date} ${time}`;
}

// Simulasi data dari backend
function fetchLogsMock() {
  const data = [
    {
      date: "2025-08-11",
      items: [
        { timestamp: "2025-08-11T10:25:43Z", ip: "127.0.0.1", userAgent: "Mozilla/5.0 ... Edg/138.0.0.0" },
        { timestamp: "2025-08-11T03:12:07Z", ip: "10.0.0.2", userAgent: "Mozilla/5.0 ... Chrome/138.0.0.0" },
        { timestamp: "2025-08-11T00:00:01Z", ip: "10.0.0.3", userAgent: "curl/8.7.1" },
      ],
    },
    {
      date: "2025-08-10",
      items: [
        { timestamp: "2025-08-10T21:00:00Z", ip: "192.168.1.10", userAgent: "Mozilla/5.0 ... Safari/537.36" },
        { timestamp: "2025-08-10T05:25:43Z", ip: "127.0.0.1", userAgent: "Mozilla/5.0 ... Edg/138.0.0.0" },
        { timestamp: "2025-08-10T01:01:43Z", ip: "127.0.0.9", userAgent: "PostmanRuntime/7.39.0" },
      ],
    },
    {
      date: "2025-08-09",
      items: [
        { timestamp: "2025-08-09T13:00:00Z", ip: "8.8.8.8", userAgent: "Mozilla/5.0 ... Firefox/128.0" },
        { timestamp: "2025-08-09T05:25:43Z", ip: "1.1.1.1", userAgent: "okhttp/4.12.0" },
      ],
    },
    {
      date: "2025-08-08",
      items: [
        { timestamp: "2025-08-08T13:00:00Z", ip: "8.8.8.8", userAgent: "Mozilla/5.0 ... Firefox/128.0" },
        { timestamp: "2025-08-08T05:25:43Z", ip: "1.1.1.1", userAgent: "okhttp/4.12.0" },
      ],
    },
  ];
  return new Promise((resolve) => setTimeout(() => resolve(data), 3000));
}

// Render data ke UI
function renderLogs(groupedData) {
  const container = document.getElementById("log-container");
  container.innerHTML = "";

  groupedData.forEach((group) => {
    // Header tanggal
    const header = document.createElement("div");
    header.className = "list-group-header sticky-top fw-semibold bg-body";
    header.textContent = `Tanggal ${formatDateHeader(group.date)}`;
    container.appendChild(header);

    // Item di bawahnya
    group.items.forEach((log) => {
      const item = document.createElement("div");
      item.className = "list-group-item";
      item.innerHTML = `
        <div class="row">
          <div class="col-auto">
            <p class="mb-1">${formatTimestamp(log.timestamp)}</p>
          </div>
          <div class="col text-truncate d-flex flex-column">
            <p class="text-body fw-bolder d-block mb-1">${log.ip}</p>
            <div class="text-secondary text-truncate">${log.userAgent}</div>
          </div>
        </div>
      `;
      container.appendChild(item);
    });
  });
}

// Render placeholder menyerupai item asli
function renderPlaceholders(count = 5) {
  const container = document.getElementById('log-container');
  container.innerHTML = '';

  const header = document.createElement('div');
  header.className = 'list-group-header sticky-top fw-semibold bg-body';
  header.textContent = 'Memuat data...';
  container.appendChild(header);

  for (let i = 0; i < count; i++) {
    const placeholderItem = document.createElement('div');
    placeholderItem.className = 'list-group-item';
    placeholderItem.innerHTML = `
      <div class="row">
        <div class="col-auto">
          <div class="placeholder placeholder-xs col-12 mb-1" style="width: 7rem;"></div>
        </div>
        <div class="col">
          <div class="placeholder placeholder-xs col-6 mb-1"></div>
          <div class="placeholder placeholder-xs col-9"></div>
        </div>
      </div>
    `;
    container.appendChild(placeholderItem);
  }
}

// Init
(async function init() {
  renderPlaceholders(6); // tampilkan placeholder dulu
  const data = await fetchLogsMock();
  renderLogs(data);
})();
