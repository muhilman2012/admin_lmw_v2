// ====== KONFIG YANG SUDAH ADA ======
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

const setPageListItems = (e) => {
  window.tabler_list["advanced-table-pengadu"].page = parseInt(e.target.dataset.value, 10);
  window.tabler_list["advanced-table-pengadu"].update();
  document.querySelector("#page-count").innerHTML = e.target.dataset.value;
};

window.tabler_list = window.tabler_list || {};

// helper: update state tombol prev/next
function updateChevronState(list) {
  const pageSize = list.page || 1;
  const total = list.matchingItems.length || 0;
  const totalPages = Math.max(1, Math.ceil(total / pageSize));
  const currentPage = Math.floor(list.i / pageSize) + 1;

  const prevLi = document.querySelector("#pagination-prev .page-item");
  const nextLi = document.querySelector("#pagination-next .page-item");

  if (prevLi) prevLi.classList.toggle("disabled", currentPage <= 1);
  if (nextLi) nextLi.classList.toggle("disabled", currentPage >= totalPages);
}

// ====== 1) DUMMY DATA & RENDERER ROWS ======
// function generateDummyPengadus(count = 300) {
//   const first = [
//     "Budi",
//     "Siti",
//     "Agus",
//     "Rina",
//     "Dewi",
//     "Ahmad",
//     "Tono",
//     "Lina",
//     "Hendra",
//     "Nina",
//     "Rudi",
//     "Intan",
//     "Yusuf",
//     "Tari",
//     "Andi",
//     "Rama",
//     "Citra",
//     "Dian",
//     "Eka",
//     "Fajar",
//     "Gita",
//     "Hari",
//     "Indra",
//     "Joko",
//     "Kiki",
//     "Lukman",
//     "Maya",
//     "Nanda",
//     "Oki",
//     "Putri",
//     "Rizky",
//     "Salsa",
//     "Taufik",
//     "Uli",
//     "Vina",
//     "Wahyu",
//     "Yuni",
//     "Zaki",
//   ];
//   const last = [
//     "Santoso",
//     "Wijaya",
//     "Saputra",
//     "Pratama",
//     "Herlina",
//     "Pratiwi",
//     "Setiawan",
//     "Kusuma",
//     "Hidayat",
//     "Maulana",
//     "Suryani",
//     "Fauzi",
//     "Firmansyah",
//     "Ramadhan",
//     "Utami",
//     "Nugroho",
//     "Ananda",
//     "Mahardika",
//     "Putra",
//     "Putri",
//   ];
//   const streets = [
//     "Jl. Merdeka",
//     "Jl. Sudirman",
//     "Jl. Thamrin",
//     "Jl. Diponegoro",
//     "Jl. Gajah Mada",
//     "Jl. Ahmad Yani",
//     "Jl. S Parman",
//     "Jl. Kenanga",
//     "Jl. Melati",
//     "Jl. Anggrek",
//     "Jl. Beringin",
//     "Jl. Mawar",
//     "Jl. Cempaka",
//     "Jl. Flamboyan",
//     "Jl. Kelapa",
//     "Jl. Cemara",
//   ];
//   const cities = [
//     "Jakarta",
//     "Bandung",
//     "Surabaya",
//     "Semarang",
//     "Yogyakarta",
//     "Medan",
//     "Makassar",
//     "Denpasar",
//     "Palembang",
//     "Bekasi",
//     "Depok",
//     "Tangerang",
//   ];

//   const data = [];
//   for (let i = 1; i <= count; i++) {
//     const fn = first[(i * 13) % first.length];
//     const ln = last[(i * 7) % last.length];
//     const nama = `${fn} ${ln}`;
//     const nik = "33" + String(Math.floor(10 ** 14 + Math.random() * 9 * 10 ** 13)).slice(0, 14); // 16 digit
//     const nohp = "08" + String(Math.floor(100000000 + Math.random() * 900000000)); // 11 digit
//     const email = `${fn}.${ln}${i}@example.com`.toLowerCase().replace(/\s+/g, "");
//     const alamat = `${streets[i % streets.length]} No. ${((i * 3) % 200) + 1}, ${cities[(i * 5) % cities.length]}`;

//     data.push({
//       nomor: i,
//       namaLengkap: nama,
//       nik,
//       nohp,
//       email,
//       alamat,
//     });
//   }
//   return data;
// }

function renderPengaduRows(rows) {
  const tbody = document.querySelector("#advanced-table-pengadu .table-tbody");
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
            <a href="#" class="btn btn-1 btn-outline-primary">
              <i class="icon ti ti-id me-2"></i>View KTP
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-input-laporan">
              <i class="icon ti ti-pencil me-2"></i>Buat Laporan
            </button>
          </div>
        </td>
      </tr>
    `
    )
    .join("");
}

// ====== 2) EMPTY STATE (tetap versi kamu) ======
function ensureEmptyRow() {
  const tbody =
    document.querySelector("#advanced-table-pengadu .table-tbody") || document.querySelector(".table-tbody");
  let emptyRow = tbody.querySelector("tr.empty-row");
  if (!emptyRow) {
    const colCount =
      document.querySelector("#advanced-table-pengadu thead tr")?.children.length || advancedTable.headers.length || 1;

    emptyRow = document.createElement("tr");
    emptyRow.className = "empty-row";
    const td = document.createElement("td");
    td.colSpan = colCount;
    td.className = "text-center text-muted p-4";
    td.innerHTML = `
        <div class="d-flex flex-column align-items-center gap-1">
          <div style="font-size: 2rem; line-height: 1;">😕</div>
          <div><strong>Tidak ada data</strong></div>
          <div class="small">Coba ubah filter atau kata kunci pencarian.</div>
        </div>
      `;
    emptyRow.appendChild(td);
    tbody.appendChild(emptyRow);
  }
  return emptyRow;
}

function toggleEmptyRow(list) {
  const emptyRow = ensureEmptyRow();
  const hasNoItems = list.matchingItems.length === 0;
  emptyRow.style.display = hasNoItems ? "" : "none";

  const paginationEl =
    document.querySelector("#advanced-table-pagination") ||
    document.querySelector("#advanced-table-pengadu .pagination") ||
    document.querySelector(".pagination");
  if (paginationEl) {
    paginationEl.style.visibility = hasNoItems ? "hidden" : "visible";
  }
}

// ====== 3) INIT: render dummy -> init List.js -> hook events ======
document.addEventListener("DOMContentLoaded", function () {
  // Render data dulu, baru init List.js supaya item terindeks
  const dummy = generateDummyPengadu(300); // ubah jumlah di sini kalau mau
  renderPengaduRows(dummy);

  const list = (window.tabler_list["advanced-table-pengadu"] = new List("advanced-table-pengadu", {
    sortClass: "table-sort",
    listClass: "table-tbody",
    page: 20,
    pagination: {
      // arahkan List.js nulis ke UL angka yang khusus
      paginationClass: "pagination-numbers",
      item: (value) => `<li class="page-item"><a class="page-link cursor-pointer">${value.page}</a></li>`,
      innerWindow: 1,
      outerWindow: 1,
      left: 1,
      right: 0,
    },
    valueNames: advancedTable.headers.map((header) => header["data-sort"]),
  }));

  // klik prev/next (di UL terpisah, tidak akan dihapus List.js)
  const prevA = document.querySelector("#pagination-prev a.page-link");
  const nextA = document.querySelector("#pagination-next a.page-link");

  if (prevA) {
    prevA.addEventListener("click", (e) => {
      e.preventDefault();
      const pageSize = list.page || 1;
      if (list.i <= 0) return;
      list.i = Math.max(0, list.i - pageSize);
      list.update();
    });
  }
  if (nextA) {
    nextA.addEventListener("click", (e) => {
      e.preventDefault();
      const pageSize = list.page || 1;
      const total = list.matchingItems.length || 0;
      const totalPages = Math.max(1, Math.ceil(total / pageSize));
      const currentPage = Math.floor(list.i / pageSize) + 1;
      if (currentPage >= totalPages) return;
      list.i = Math.min((totalPages - 1) * pageSize, list.i + pageSize);
      list.update();
    });
  }

  // Search (kalau ada inputnya)
  const searchInput = document.querySelector("#advanced-table-pengadu-search");
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      list.search(searchInput.value);
      toggleEmptyRow(list);
      updateChevronState(list);
    });
  }

  // Update hook
  list.on("updated", () => {
    toggleEmptyRow(list);
    updateChevronState(list); // pastikan state prev/next selalu sinkron
    // --- (Opsional) nomor urut mengikuti halaman yang tampil ---
    // renumberVisibleRows(list);
  });

  // State awal
  toggleEmptyRow(list);
  updateChevronState(list);

  // Expose helper kalau nanti mau reload data dari API
  window.reloadPengaduRows = function (rows) {
    renderPengaduRows(rows);
    list.reIndex();
    list.update();
  };
});

// ====== 4) (Opsional) fungsi untuk menomori ulang sesuai halaman ======
function renumberVisibleRows(list) {
  const tbody = document.querySelector("#advanced-table-pengadu .table-tbody");
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll("tr:not(.empty-row)"));
  rows.forEach((tr, idx) => {
    const cell = tr.querySelector(".sort-nomor");
    if (cell) cell.textContent = idx + 1 + list.i; // list.i = offset item pertama pada halaman ini
  });
}
