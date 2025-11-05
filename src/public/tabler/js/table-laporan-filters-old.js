/* laporan-filters.js
   KUMPULAN FUNGSI FILTER untuk halaman Laporan.
   Catatan:
   - Fungsi yang disini itu aslinya ada di file table-laporan-data.js.
   - Fungsi ini sudah dipisah ke file table-laporan-filters.js agar lebih terorganisir
*/

/* =======================
   UTIL TANGGAL & SORT
   ======================= */
function parseDMY(s) {
  if (!s) return NaN;
  const [d, m, y] = s.split(/[\/\-]/).map(Number);
  return new Date(y, (m || 1) - 1, d || 1).getTime();
}

/* =======================
   POPULATE & LOGIC FILTER (modal)
   ======================= */
// Isi opsi filter dari data
function populateFilterOptionsFromData(rows) {
  const katSel = document.getElementById("filter-kategori");
  const statSel = document.getElementById("filter-status");
  const distSel = document.getElementById("filter-distribusi");
  const dispSel = document.getElementById("filter-disposisi");
  const sumSel = document.getElementById("filter-sumber");
  const fill = (sel, items, firstLabel) => {
    if (!sel) return;
    const opts = [...new Set(items)].sort();
    sel.innerHTML =
      `<option value="">${firstLabel}</option>` + opts.map((v) => `<option value="${v}">${v}</option>`).join("");
  };
  fill(
    katSel,
    rows.map((x) => x.kategori),
    "Semua Kategori"
  );
  fill(
    statSel,
    rows.map((x) => x.status),
    "Semua Status"
  );
  fill(
    distSel,
    rows.map((x) => x.distribusi),
    "Semua Distribusi"
  );
  fill(
    dispSel,
    rows.map((x) => x.disposisi),
    "Semua Disposisi"
  );
  fill(
    sumSel,
    rows.map((x) => x.sumber),
    "Semua Sumber"
  );
}

// Terapkan filter dari modal
function applyFilters(list) {
  const kategori = (document.getElementById("filter-kategori")?.value || "").trim();
  const status = (document.getElementById("filter-status")?.value || "").trim();
  const distribusi = (document.getElementById("filter-distribusi")?.value || "").trim();
  const disposisi = (document.getElementById("filter-disposisi")?.value || "").trim();
  const sumber = (document.getElementById("filter-sumber")?.value || "").trim();
  const rangeVal = (document.getElementById("filter-date-range")?.value || "").trim();
  const sortBy = document.getElementById("filter-sort")?.value || "terbaru";

  let fromTs = -Infinity,
    toTs = Infinity;
  if (rangeVal && rangeVal.includes(" - ")) {
    const [from, to] = rangeVal.split(" - ");
    fromTs = parseDMY(from);
    toTs = parseDMY(to);
  }

  list.filter((item) => {
    const v = item.values();
    if (kategori && v["sort-kategori"] !== kategori) return false;
    if (status && v["sort-status"] !== status) return false;
    if (distribusi && v["sort-distribusi"] !== distribusi) return false;
    if (disposisi && v["sort-disposisi"] !== disposisi) return false;
    if (sumber && v["sort-sumber"] !== sumber) return false;

    const t = parseDMY(v["sort-dikirim"]);
    if (!isNaN(fromTs) && t < fromTs) return false;
    if (!isNaN(toTs) && t > toTs) return false;

    return true;
  });

  // sort by tanggal
  const dir = sortBy === "terbaru" ? -1 : 1;
  list.sort("sort-dikirim", {
    sortFunction: (a, b) => dir * (parseDMY(a.values()["sort-dikirim"]) - parseDMY(b.values()["sort-dikirim"])),
  });

  toggleEmptyRow(list);
  updateChevronState(list);
}

function resetFilters(list) {
  [
    "filter-kategori",
    "filter-status",
    "filter-distribusi",
    "filter-disposisi",
    "filter-sumber",
    "filter-date-range",
    "filter-sort",
  ].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.value = id === "filter-sort" ? "terbaru" : "";
  });

  list.filter(); // clear
  list.sort("sort-nomor", {
    sortFunction: (a, b) => parseInt(a.values()["sort-nomor"]) - parseInt(b.values()["sort-nomor"]),
  });
  toggleEmptyRow(list);
  updateChevronState(list);
  updateFilterBadge();
}

function getActiveFilterCount() {
  // ambil nilai filter (kecuali 'Urutkan')
  const kategori = (document.getElementById("filter-kategori")?.value || "").trim();
  const status = (document.getElementById("filter-status")?.value || "").trim();
  const distribusi = (document.getElementById("filter-distribusi")?.value || "").trim();
  const disposisi = (document.getElementById("filter-disposisi")?.value || "").trim();
  const sumber = (document.getElementById("filter-sumber")?.value || "").trim();
  const rangeVal = (document.getElementById("filter-date-range")?.value || "").trim();

  let count = 0;
  if (kategori) count++;
  if (status) count++;
  if (distribusi) count++;
  if (disposisi) count++;
  if (sumber) count++;

  // hitung date range kalau keduanya diisi (format "DD/MM/YYYY - DD/MM/YYYY")
  if (rangeVal && rangeVal.includes(" - ")) {
    const [from, to] = rangeVal.split(" - ");
    if (from.trim() && to.trim()) count++;
  }

  console.log(count);
  return count;
}

function updateFilterBadge() {
  const n = getActiveFilterCount();
  const badge = document.getElementById("filter-active-count");
  if (!badge) return;
  if (n > 0) {
    badge.textContent = n;
    badge.classList.remove("d-none");
  } else {
    badge.classList.add("d-none");
  }

  console.log(n);
}

// util close aman (pakai Bootstrap kalau ada, kalau tidak fallback)
function closeModalSafe(modalId, focusEl) {
  const modalEl = document.getElementById(modalId);
  if (!modalEl) return;
  if (window.bootstrap && window.bootstrap.Modal) {
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
  } else {
    // fallback manual
    if (focusEl && focusEl.focus) focusEl.focus({ preventScroll: true });
    modalEl.classList.remove("show");
    modalEl.setAttribute("aria-hidden", "true");
    modalEl.style.display = "none";
    document.body.classList.remove("modal-open");
    document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());
  }
}
