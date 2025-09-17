/* =======================
   TABEL LAPORAN (List.js)
   ======================= */

const laporanTableConfig = {
  headers: [
    { "data-sort": "sort-no", name: "#" },
    { "data-sort": "sort-tiket", name: "Nomor Tiket" },
    { "data-sort": "sort-nama", name: "Nama Lengkap" },
    { "data-sort": "sort-judul", name: "Judul Pengaduan" },
    { "data-sort": "sort-kategori", name: "Kategori" },
    { "data-sort": "sort-distribusi", name: "Distribusi" },
    { "data-sort": "sort-disposisi", name: "Disposisi" },
    { "data-sort": "sort-sumber", name: "Sumber" },
    { "data-sort": "sort-status", name: "Status" },
    { "data-sort": "sort-dikirim", name: "Dikirim" },
  ],
};

function setLaporanPageSize(e) {
  const l = window.tabler_list["advanced-table-laporan"];
  l.page = parseInt(e.target.dataset.value, 10);
  l.update();
  document.querySelector("#page-count").textContent = e.target.dataset.value;
  updateChevronStateLaporan(l);
  renumberVisibleRowsLaporan(l);
}

window.setLaporanPageSize = setLaporanPageSize; // biar bisa dipanggil dari HTML

/* ---------- utils ---------- */
function $(sel, root = document) {
  return root.querySelector(sel);
}
function parseDMY(s) {
  if (!s) return NaN;
  const [d, m, y] = s.split(/[\/\-]/).map(Number);
  return new Date(y, (m || 1) - 1, d || 1).getTime();
}

const PREV_BTN = "#pagination-prev a.page-link";
const NEXT_BTN = "#pagination-next a.page-link";

/* ---------- render <tr> ---------- */
function renderLaporanRows(rows) {
  const tbody = $("#advanced-table-laporan .table-tbody");
  if (!tbody) return;
  tbody.innerHTML = rows
    .map(
      (r) => `
    <tr>
      <td class="sort-no">${r.no}</td>
      <td class="sort-tiket"><a href="#" class="text-blue">${r.tiket}</a></td>
      <td class="sort-nama">${r.nama}</td>
      <td class="sort-judul">${r.judul}</td>
      <td class="sort-kategori">${r.kategori}</td>
      <td class="sort-distribusi">${r.distribusi}</td>
      <td class="sort-disposisi ${/Belum/.test(r.disposisi) ? "text-danger" : "text-success"}">${r.disposisi}</td>
      <td><span class="sort-sumber badge bg-primary-lt">${r.sumber}</span></td>
      <td><span class="sort-status badge ${window.statusToColor ? window.statusToColor(r.status) : "bg-secondary"}">${
        r.status
      }</span></td>
      <td class="sort-dikirim">${r.dikirim}</td>
      <td>
        <div class="btn-list flex-nowrap">
          <a class="btn btn-icon btn-outline-secondary btn-view" 
            href="view-laporan-pengaduan.html?tiket=${encodeURIComponent(r.tiket)}" 
            title="Lihat">
            <i class="ti ti-eye"></i>
          </a>
          <a class="btn btn-icon btn-outline-secondary btn-edit"
            href="edit-laporan-pengaduan.html?tiket=${encodeURIComponent(r.tiket)}"
            title="Edit">
            <i class="ti ti-pencil"></i>
          </a>
            <button class="btn btn-icon btn-outline-danger btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modal-confirm-delete"
                data-ticket="${r.tiket}"
                title="Hapus">
                <i class="ti ti-trash"></i>
            </button>
        </div>
      </td>
    </tr>
  `
    )
    .join("");
}

function ensureEmptyRowLaporan() {
  const tbody = $("#advanced-table-laporan .table-tbody");
  let emptyRow = tbody.querySelector("tr.empty-row");
  if (!emptyRow) {
    const colCount = $("#advanced-table-laporan thead tr")?.children.length || advancedTable.headers.length || 1;
    emptyRow = document.createElement("tr");
    emptyRow.className = "empty-row";
    const td = document.createElement("td");
    td.colSpan = colCount;
    td.className = "text-center text-muted p-4";
    td.innerHTML = `
      <div class="d-flex flex-column align-items-center gap-1">
        <div style="font-size:2rem;line-height:1;">😕</div>
        <div><strong>Tidak ada data</strong></div>
        <div class="small">Coba ubah kata kunci pencarian.</div>
      </div>`;
    emptyRow.appendChild(td);
    tbody.appendChild(emptyRow);
  }
  return emptyRow;
}

function toggleEmptyRowLaporan(list) {
  const emptyRow = ensureEmptyRowLaporan();
  emptyRow.style.display = list.matchingItems.length === 0 ? "" : "none";
  const paginationEl = $("#advanced-table-laporan .card-footer .pagination");
  if (paginationEl) paginationEl.style.visibility = list.matchingItems.length === 0 ? "hidden" : "visible";
}

// Penomoran baris mengikuti halaman yang tampil (termasuk saat search/sort)
function renumberVisibleRowsLaporan(list) {
  const startOffset = list.i || 0; // 0-based index item pertama di halaman aktif
  const tbody = document.querySelector("#advanced-table-laporan .table-tbody");
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll("tr:not(.empty-row)"));
  rows.forEach((tr, idx) => {
    const cell = tr.querySelector(".sort-nomor");
    if (cell) cell.textContent = startOffset + idx;
  });
}

function updateChevronStateLaporan(list) {
  const pageSize = list.page || 1;
  const totalItems = list.matchingItems ? list.matchingItems.length : 0;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const current = Math.floor((list.i || 0) / pageSize) + 1; // 1-based

  const prevLi = document.querySelector("#pagination-prev .page-item");
  const nextLi = document.querySelector("#pagination-next .page-item");
  if (prevLi) prevLi.classList.toggle("disabled", current <= 1);
  if (nextLi) nextLi.classList.toggle("disabled", current >= totalPages);
}

// Klik prev/next → “klikkan” nomor halaman bawaan List.js
function wirePrevNext() {
  const prevA = $(PREV_BTN);
  const nextA = $(NEXT_BTN);
  prevA?.addEventListener("click", (e) => {
    e.preventDefault();
    document
      .querySelector(".pagination-numbers .page-item.active")
      ?.previousElementSibling?.querySelector("a.page-link")
      ?.click();
  });
  nextA?.addEventListener("click", (e) => {
    e.preventDefault();
    document
      .querySelector(".pagination-numbers .page-item.active")
      ?.nextElementSibling?.querySelector("a.page-link")
      ?.click();
  });
}

/* ================== INIT ================== */
window.tabler_list = window.tabler_list || {};

/* ---------- INIT ---------- */
document.addEventListener("DOMContentLoaded", () => {
  // 1) Render awal dari dummy
  const rows = window.laporanData || [];
  renderLaporanRows(rows);
  populateFilterOptionsFromData(rows);

  // 2) Init List.js
  const list = (window.tabler_list["advanced-table-laporan"] = new List("advanced-table-laporan", {
    sortClass: "table-sort",
    listClass: "table-tbody",
    page: 20,
    pagination: {
      paginationClass: "pagination-numbers",
      item: (v) => `<li class="page-item"><a class="page-link cursor-pointer">${v.page}</a></li>`,
      innerWindow: 0,
      outerWindow: 0,
      left: 1,
      right: 1,
    },
    valueNames: laporanTableConfig.headers.map((h) => h["data-sort"]),
  }));

  // 3) Hook search
  const searchInput = $("#advanced-table-laporan-search");
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      list.search(searchInput.value);
      toggleEmptyRowLaporan(list);
      updateChevronStateLaporan(list);
      renumberVisibleRowsLaporan(list);
      // Tunggu layout microtask lalu sync dari DOM
      queueMicrotask(() => {
        updateChevronStateLaporan();
        renumberVisibleRowsLaporan(list);
      });
    });
  }
  // 5) Sync state
  list.on("updated", () => {
    toggleEmptyRowLaporan(list);
    updateChevronStateLaporan(list);
    renumberVisibleRowsLaporan(list);
  });

  // pasang prev/next kustom
  wirePrevNext();

  toggleEmptyRowLaporan(list);
  updateChevronStateLaporan(list);
  renumberVisibleRowsLaporan(list);
  // microtask supaya pagination numbers sempat ter-render dulu
  queueMicrotask(() => {
    updateChevronStateLaporan();
    renumberVisibleRowsLaporan(list);
  });

  if (window.Litepicker) {
    new Litepicker({
      element: document.getElementById("filter-date-range"),
      singleMode: false,
      format: "DD/MM/YYYY",
    });
  }

  // 7) tombol Apply / Reset filter
  document.getElementById("btn-filter-apply")?.addEventListener("click", () => {
    applyFilters(list);
    updateFilterBadge(); // <-- update badge dulu
  });

  document.getElementById("btn-filter-reset")?.addEventListener("click", () => {
    resetFilters(list);
    updateFilterBadge(); // badge kembali hidden
  });

  // 8) helper reload data nanti
  window.reloadLaporanRows = function (rows) {
    renderLaporanRows(rows);
    populateFilterOptionsFromData(rows);
    list.reIndex();
    list.update();
  };

  updateFilterBadge();
});
