/* laporan-filters.js
   KUMPULAN FUNGSI FILTER untuk halaman Laporan.
   Catatan:
   - Semua fungsi diekspor ke window dengan NAMA YANG SAMA agar kompatibel
     dengan pemanggilan di table-laporan-data-new.js (zero changes).
   - Mengandalkan window.laporanData (array data) dan window.reloadLaporanRows(rows)
     yang sudah disediakan di table-laporan-data-new.js.
*/

(function () {
  // ===== helpers =====
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // state filter (opsional dipakai internal)
  const FILTER_STATE = {
    kategori: "",
    distribusi: "",
    disposisi: "",
    status: "",
    sumber: "",
    from: "", // string tanggal
    to: "", // string tanggal
  };

  // parse tanggal “DMY”
  function parseDMY(s) {
    if (!s) return NaN;
    const [d, m, y] = s.split(/[\/\-]/).map(Number);
    return new Date(y, (m || 1) - 1, d || 1).getTime();
  }

  function closeModalSafe(modalEl) {
    if (!modalEl) return;
    if (window.bootstrap?.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      return;
    }
    // fallback manual
    modalEl.classList.remove("show");
    modalEl.setAttribute("aria-hidden", "true");
    modalEl.style.display = "none";
    document.body.classList.remove("modal-open");
    $$(".modal-backdrop").forEach((el) => el.remove());
  }

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

    return count;
  }

  function updateFilterBadge() {
    const badge = $("#filter-active-count");
    if (!badge) return;
    const n = getActiveFilterCount();
    badge.textContent = String(n);
    badge.classList.toggle("d-none", n === 0);
  }

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

    toggleEmptyRowLaporan(list);
    updateChevronStateLaporan(list);
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
    toggleEmptyRowLaporan(list);
    updateChevronStateLaporan(list);
    updateFilterBadge();
  }

  // ===== expose ke window (kompatibel dengan pemanggilan lama) =====
  window.parseDMY = parseDMY;
  window.populateFilterOptionsFromData = populateFilterOptionsFromData;
  window.applyFilters = applyFilters;
  window.resetFilters = resetFilters;
  window.getActiveFilterCount = getActiveFilterCount;
  window.updateFilterBadge = updateFilterBadge;
  window.closeModalSafe = closeModalSafe;

  // (opsional) satu namespace juga disediakan
  window.laporanFilters = {
    state: FILTER_STATE,
    parseDMY,
    populateFilterOptionsFromData,
    applyFilters,
    resetFilters,
    getActiveFilterCount,
    updateFilterBadge,
    closeModalSafe,
  };
})();
