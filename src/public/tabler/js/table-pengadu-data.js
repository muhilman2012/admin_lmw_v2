/* table-pengadu-data.js — versi state-based, robust terhadap item "…" pada pagination */

const advancedTable = {
  headers: [
    { "data-sort": "sort-nomor", name: "Nomor" },
    { "data-sort": "sort-nama-lengkap", name: "Nama Lengkap" },
    { "data-sort": "sort-nik", name: "NIK" },
    { "data-sort": "sort-nohp", name: "Nomor HP" },
    { "data-sort": "sort-email", name: "Email" },
    { "data-sort": "sort-alamat", name: "Alamat" },
  ],
};

/* ===== page size dropdown ===== */
function setPageListItems(e) {
  const l = window.tabler_list["advanced-table-pengadu"];
  l.page = parseInt(e.target.dataset.value, 10);
  l.update();
  document.querySelector("#page-count").textContent = e.target.dataset.value;
  updateChevronState(l);
  renumberVisibleRows(l);
}
window.setPageListItems = setPageListItems;

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const PAGINATION_NUMBERS = "#advanced-table-pengadu .pagination-numbers";
const PREV_BTN_PENGADU = "#pagination-prev a.page-link";
const NEXT_BTN_PENGADU = "#pagination-next a.page-link";

/* ===== renderer rows ===== */
function renderPengaduRows(rows) {
  const tbody = $("#advanced-table-pengadu .table-tbody");
  if (!tbody) return;
  tbody.innerHTML = rows
    .map(
      (row) => `
    <tr>
      <td class="sort-nomor">${row.nomor}</td>
      <td class="sort-nama-lengkap">${row.namaLengkap}</td>
      <td class="sort-nik">${row.nik}</td>
      <td class="sort-nohp">${row.nohp}</td>
      <td class="sort-email">${row.email}</td>
      <td class="sort-alamat">${row.alamat}</td>
      <td>
        <div class="btn-list flex-nowrap">
          <a href="#" class="btn btn-1 btn-outline-primary"><i class="ti ti-id me-2"></i>View KTP</a>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-input-laporan">
            <i class="ti ti-pencil me-2"></i>Buat Laporan
          </button>
        </div>
      </td>
    </tr>
  `
    )
    .join("");
}

/* ===== empty state ===== */
function ensureEmptyRow() {
  const tbody = $("#advanced-table-pengadu .table-tbody");
  let emptyRow = tbody.querySelector("tr.empty-row");
  if (!emptyRow) {
    const colCount = $("#advanced-table-pengadu thead tr")?.children.length || advancedTable.headers.length || 1;
    emptyRow = document.createElement("tr");
    emptyRow.className = "empty-row";
    const td = document.createElement("td");
    td.colSpan = colCount;
    td.className = "text-center text-muted p-4";
    td.innerHTML = `
      <div class="d-flex flex-column align-items-center gap-1">
        <div style="font-size:2rem;line-height:1;">😕</div>
        <div><strong>Tidak ada data</strong></div>
        <div class="small">Coba ubah filter atau kata kunci pencarian.</div>
      </div>`;
    emptyRow.appendChild(td);
    tbody.appendChild(emptyRow);
  }
  return emptyRow;
}
function toggleEmptyRow(list) {
  const emptyRow = ensureEmptyRow();
  emptyRow.style.display = list.matchingItems.length === 0 ? "" : "none";
  const paginationEl = $("#advanced-table-pengadu .card-footer .pagination");
  if (paginationEl) paginationEl.style.visibility = list.matchingItems.length === 0 ? "hidden" : "visible";
}

function renumberVisibleRows(list) {
  const startOffset = list.i || 0; // 0-based index item pertama di halaman aktif
  const tbody = document.querySelector("#advanced-table-user .table-tbody");
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll("tr:not(.empty-row)"));
  rows.forEach((tr, idx) => {
    const cell = tr.querySelector(".sort-nomor");
    if (cell) cell.textContent = startOffset + idx;
  });
}

/* ===== chevron state & renumber ===== */
function updateChevronState(list) {
  const pageSize = list.page || 1;
  const totalItems = list.matchingItems ? list.matchingItems.length : 0;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const current = Math.floor((list.i || 0) / pageSize) + 1; // 1-based

  const prevLi = $("#pagination-prev .page-item");
  const nextLi = $("#pagination-next .page-item");
  if (prevLi) prevLi.classList.toggle("disabled", current <= 1);
  if (nextLi) nextLi.classList.toggle("disabled", current >= totalPages);
}

/* ===== wire prev/next (pakai goToPage, tidak mengandalkan DOM tetangga/ellipsis) ===== */
function wirePrevNext(list) {
  const prevA = $(PREV_BTN_PENGADU);
  const nextA = $(NEXT_BTN_PENGADU);
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

/* ===== INIT ===== */
window.tabler_list = window.tabler_list || {};

document.addEventListener("DOMContentLoaded", () => {
  // data dari generator dummy (pengadu-dummy-data.js)
  const seed = window.pengaduData || [];
  renderPengaduRows(seed);

  const list = (window.tabler_list["advanced-table-pengadu"] = new List("advanced-table-pengadu", {
    listClass: "table-tbody",
    sortClass: "table-sort",
    valueNames: advancedTable.headers.map((h) => h["data-sort"]),
    page: 20,
    pagination: {
      paginationClass: "pagination-numbers",
      item: (v) => `<li class="page-item"><a class="page-link cursor-pointer">${v.page}</a></li>`,
      innerWindow: 0,
      outerWindow: 0,
      left: 1,
      right: 1,
    },
  }));

  // search
  const searchInput = $("#advanced-table-pengadu-search");
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

  // hook setiap update (sort/search/paginate)
  list.on("updated", () => {
    toggleEmptyRow(list);
    updateChevronState(list);
    renumberVisibleRows(list);
  });

  // prev/next
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
