/* dummy-data-management-user.js (replaced) */

/* ================== CONFIG ================== */
const advancedTable = {
  headers: [
    { "data-sort": "sort-no", name: "Nomor" },
    { "data-sort": "sort-nama", name: "Nama" },
    { "data-sort": "sort-email", name: "Email" },
    { "data-sort": "sort-role", name: "Role" },
    { "data-sort": "sort-jabatan", name: "Jabatan" },
    { "data-sort": "sort-unit", name: "Unit" },
  ],
};

// Ganti page size via dropdown
function setUserPageSize(e) {
  const l = window.tabler_list["advanced-table-user"];
  l.page = parseInt(e.target.dataset.value, 10);
  l.update();
  document.querySelector("#user-page-count").textContent = e.target.dataset.value;
  updateChevronState(l);
  renumberVisibleRows(l);
}
window.setUserPageSize = setUserPageSize; // biar bisa dipanggil dari HTML

/* ================== HELPERS ================== */
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const PREV_BTN = "#user-pagination-prev a.page-link";
const NEXT_BTN = "#user-pagination-next a.page-link";

function renderRows(rows) {
  const tbody = $("#tbl-user-tbody");
  tbody.innerHTML = rows
    .map(
      (u, idx) => `
    <tr data-id="${u.id}">
      <td class="sort-no">${idx + 1}</td>
      <td class="sort-nama">${u.nama}</td>
      <td class="sort-email"><a href="mailto:${u.email}">${u.email}</a></td>
      <td class="sort-role"><span class="badge bg-blue-lt">${u.role}</span></td>
      <td class="sort-jabatan">${u.jabatan || "-"}</td>
      <td class="sort-unit">${u.unit || "-"}</td>
      <td>
        <div class="btn-list flex-nowrap">
          <button class="btn btn-1 btn-outline-primary btn-edit" title="Edit"><i class="ti ti-pencil"></i></button>
          <button class="btn btn-1 btn-outline-danger  btn-del"  title="Hapus"><i class="ti ti-trash"></i></button>
        </div>
      </td>
    </tr>
  `
    )
    .join("");
}

// Empty state row di dalam tbody
function ensureEmptyRow() {
  const tbody = $("#advanced-table-user .table-tbody");
  let emptyRow = tbody.querySelector("tr.empty-row");
  if (!emptyRow) {
    const colCount = $("#advanced-table-user thead tr")?.children.length || advancedTable.headers.length || 1;
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

function toggleEmptyRow(list) {
  const emptyRow = ensureEmptyRow();
  emptyRow.style.display = list.matchingItems.length === 0 ? "" : "none";
  const paginationEl = $("#advanced-table-user .card-footer .pagination");
  if (paginationEl) paginationEl.style.visibility = list.matchingItems.length === 0 ? "hidden" : "visible";
}

// Penomoran baris mengikuti halaman yang tampil (termasuk saat search/sort)
function renumberVisibleRows(list) {
  const startOffset = list.i || 0; // 0-based index item pertama di halaman aktif
  const tbody = document.querySelector("#advanced-table-user .table-tbody");
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll("tr:not(.empty-row)"));
  rows.forEach((tr, idx) => {
    const cell = tr.querySelector(".sort-no");
    if (cell) cell.textContent = startOffset + idx;
  });
}

// Enable/disable prev/next berdasar nomor halaman di DOM
function updateChevronState(list) {
  const pageSize = list.page || 1;
  const totalItems = list.matchingItems ? list.matchingItems.length : 0;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const current = Math.floor((list.i || 0) / pageSize) + 1; // 1-based

  const prevLi = document.querySelector("#user-pagination-prev .page-item");
  const nextLi = document.querySelector("#user-pagination-next .page-item");
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

document.addEventListener("DOMContentLoaded", () => {
  // data dari generator dummy (pengadu-dummy-data.js)
  const seed = window.userData || [];
  renderRows(seed);


  // init List.js
  const list = (window.tabler_list["advanced-table-user"] = new List("advanced-table-user", {
    listClass: "table-tbody",
    sortClass: "table-sort",
    valueNames: advancedTable.headers.map((h) => h["data-sort"]),
    page: 20,
    pagination: {
      paginationClass: "pagination-numbers",
      item: (value) => `<li class="page-item"><a class="page-link cursor-pointer">${value.page}</a></li>`,
      innerWindow: 0,
      outerWindow: 0,
      left: 1,
      right: 1,
    },
  }));

  // search
  const searchInput = $("#advanced-table-manage-user-search");
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      list.search(searchInput.value);
      toggleEmptyRow(list);
      updateChevronState(list);
      renumberVisibleRows(list);
      // Tunggu layout microtask lalu sync dari DOM
      queueMicrotask(() => {
        updateChevronState();
        renumberVisibleRows(list);
      });
    });
  }

  // hook ketika pagination/sort/filter berubah
  list.on("updated", () => {
    toggleEmptyRow(list);
    updateChevronState(list);
    renumberVisibleRows(list);
  });

  // pasang prev/next kustom
  wirePrevNext();

  // state awal
  toggleEmptyRow(list);
  updateChevronState(list);
  renumberVisibleRows(list);
  // microtask supaya pagination numbers sempat ter-render dulu
  queueMicrotask(() => {
    updateChevronState();
    renumberVisibleRows(list);
  });
});
