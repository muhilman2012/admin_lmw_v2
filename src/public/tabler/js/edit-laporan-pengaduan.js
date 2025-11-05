// edit-laporan.js
(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  function text(id, val, fallback = "-") {
    const el = $(id);
    if (el) el.textContent = (val && String(val).trim()) || fallback;
  }

  function badge(id, label, cls) {
    const el = $(id);
    if (!el) return;
    el.innerHTML = label || "-";
    el.className = "badge " + (cls || "bg-secondary");
  }
  // mapping warna status utama (pakai statusToColor kalau sudah ada)
  function statusColor(status) {
    return window.statusToColor ? window.statusToColor(status) : "bg-secondary";
  }

  function getQuery(name) {
    const url = new URL(location.href);
    return url.searchParams.get(name);
  }
  function uniq(arr) {
    return [...new Set(arr.filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b)));
  }

  function fillSelect(el, values, current) {
    if (!el) return;
    el.innerHTML = values.map((v) => `<option value="${v}">${v}</option>`).join("");
    if (current) el.value = current;
  }

  function populateSelectOptionsFromData(data) {
    const kategori = uniq(data.map((d) => d.kategori));
    const distribusi = uniq(data.map((d) => d.distribusi));
    const disposisi = uniq(data.map((d) => d.disposisi));
    const statusSelect = uniq(data.map((d) => d.status));

    fillSelect($("#ed-kategori"), kategori);
    fillSelect($("#ed-distribusi"), distribusi);
    fillSelect($("#ed-disposisi"), disposisi);
    fillSelect($("#ed-status-select"), statusSelect);
  }

  function fillEditForm(d) {
    if (!d) return;
    badge("#ed-status", d.status, statusColor(d.status));
    text("#ed-detail", d.detail || d.uraian || d.ringkasan);
    text("#ed-tiket", d.tiket);
    text("#ed-nama", d.nama);
    text("#ed-judul", d.judul);
    text("#ed-kategori", d.kategori);
    text("#ed-distribusi", d.distribusi);
    text("#ed-disposisi", d.disposisi);
    text("#ed-tanggapan", d.tanggapan);
    text("#ed-tanggapan-textarea", d.tanggapan);
    $("#ed-status-select").value = d.status || "";
    const viewLink = $("#ed-view-link");
    if (viewLink) viewLink.href = `view-laporan-pengaduan.html?tiket=${encodeURIComponent(d.tiket)}`;
  }

  function saveForm() {
    const tiket = $("#ed-tiket").innerHTML;
    const arr = window.laporanData || [];
    const idx = arr.findIndex((x) => String(x.tiket) === String(tiket));

    arr[idx] = {
      ...arr[idx],
      nama: $("#ed-nama").innerHTML,
      judul: $("#ed-judul").innerHTML,
      kategori: $("#ed-kategori").innerHTML,
      distribusi: $("#ed-distribusi").innerHTML,
      disposisi: $("#ed-disposisi").innerHTML,
      status: $("#ed-status-select").value,
      tanggapan: $("#ed-tanggapan-textarea").value,
    };

    // TODO: ganti ke POST/PUT API nanti
  }

  document.addEventListener("DOMContentLoaded", () => {
    const tiket = getQuery("tiket");
    const data = window.laporanData || [];

    populateSelectOptionsFromData(data);

    const row = data.find((x) => String(x.tiket) === String(tiket));
    fillEditForm(row);

    // submit
    $("#form-edit-laporan")?.addEventListener("submit", (e) => {
      e.preventDefault();
      saveForm();
      // opsi: kembali ke halaman view/daftar
      // location.href = `view-laporan.html?tiket=${encodeURIComponent($('#ed-tiket').value)}`;
      // atau tampilkan toast
      alert("Perubahan disimpan (dummy).");
    });
  });
})();
