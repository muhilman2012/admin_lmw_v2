// ===== Detail data tambahan (contoh sederhana, bisa kamu ganti dari API) =====
const ticketDetails = Object.fromEntries(
  laporanData.map((t) => [
    t.tiket,
    {
      tiket: t.tiket,
      nik: "1210194906050000",
      email: (t.nama.split(" ")[0] + "." + t.nama.split(" ").slice(-1)[0] + "@example.com").toLowerCase(),
      nama: t.nama,
      nohp: "0821" + String(300000000 + Math.floor(Math.random() * 699999999)).slice(0, 7),
      kategori: t.kategori,
      judul: t.judul,
      distribusi: t.distribusi,
      disposisi: t.disposisi,
      sumber: t.sumber,
      status: t.status,
      statusAnalisis: "Pending",
      alamat: "puri lestari blok f no. 48, kel. sukajaya, cibitung, kab. bekasi, jawa barat.",
      tglKejadian: "", // kosong = Tidak diisi
      waktuPengaduan: t.dikirim + ", 08:30",
      detail: "Ringkasan laporan — bisa diisi dari deskripsi asli pengguna.",
      dokumen: "Tidak ada dokumen.",
      tanggapan: "—",
      catatanDisposisi: "Tidak ada catatan disposisi",
      analisisJF: "Belum ada analisis",
    },
  ])
);

// ===== Dummy log per tiket (ganti ke data API bila siap) =====
window.activityLogs = {
  1974001: [
    { ts: "2025-03-18 15:16:38", user: "Superadmin", text: "Disposisi Analis dihapus" },
    { ts: "2025-02-18 02:06:04", user: "Superadmin", text: "Perubahan disposisi analis" },
    {
      ts: "2025-02-05 21:33:19",
      user: "Analis Deputi 1",
      text: "Kategori diperbarui menjadi Mudik, disposisi terbaru diperbarui menjadi deputi_1",
    },
    { ts: "2025-02-05 21:12:44", user: "Superadmin", text: "Laporan ditugaskan ke analis Deputi 1" },
    { ts: "2025-02-05 20:58:13", user: "Superadmin", text: "Laporan dilimpahkan ke Deputi Bidang Administrasi" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
    { ts: "2025-02-01 22:12:41", user: "Superadmin", text: "Kategori diperbarui menjadi Bantuan Masyarakat" },
  ],
  // tiket lain menyusul...
};

// ===== Helper aman =====
function escapeHtml(s) {
  return String(s ?? "").replace(
    /[&<>"']/g,
    (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m])
  );
}

// (opsional) ubah format tanggal; default biarkan apa adanya
function formatTs(ts) {
  return ts;
}

// ===== Render log ke modal view =====
function renderActivityLog(tiket) {
  const tbody = document.getElementById("vw-log-body");
  if (!tbody) return;

  const logs = (window.activityLogs && window.activityLogs[tiket]) || [];
  if (!logs.length) {
    tbody.innerHTML = `<tr><td colspan="3" class="text-secondary text-center py-4">Tidak ada log aktivitas.</td></tr>`;
    return;
  }

  tbody.innerHTML = logs
    .map(
      (l) => `
    <tr>
      <td class="text-muted">${escapeHtml(formatTs(l.ts))}</td>
      <td>${escapeHtml(l.text)}</td>
      <td class="text-muted">${escapeHtml(l.user)}</td>
    </tr>
  `
    )
    .join("");
}

// --- daftar kelas bg yang mungkin dipakai, untuk dibersihkan sebelum set baru
const BADGE_BG_CLASSES = [
  "bg-primary",
  "bg-primary-lt",
  "bg-secondary",
  "bg-secondary-lt",
  "bg-success",
  "bg-success-lt",
  "bg-danger",
  "bg-danger-lt",
  "bg-warning",
  "bg-warning-lt",
  "bg-info",
  "bg-info-lt",
  "bg-blue",
  "bg-blue-lt",
  "bg-indigo",
  "bg-indigo-lt",
  "bg-azure",
  "bg-azure-lt",
  "bg-purple",
  "bg-purple-lt",
  "bg-orange",
  "bg-orange-lt",
  "bg-teal",
  "bg-teal-lt",
];

// aturan pewarnaan status laporan (sesuaikan kata kunci sesuai kebutuhanmu)
const STATUS_BADGE_MAP = [
  { match: /selesai/i, cls: "bg-success-lt" },
  { match: /(ditolak|tolak)/i, cls: "bg-danger-lt" },
  { match: /(verifikasi|telaah|review)/i, cls: "bg-warning-lt" },
  { match: /(proses|diproses|penanganan)/i, cls: "bg-blue-lt" },
  { match: /(disposisi|didisposisi)/i, cls: "bg-indigo-lt" },
  { match: /(pending|menunggu)/i, cls: "bg-secondary-lt" },
];

// (opsional) status analisis punya mapping sendiri
const ANALISIS_BADGE_MAP = [
  { match: /pending/i, cls: "bg-secondary-lt" },
  { match: /(proses|review|telaah)/i, cls: "bg-warning-lt" },
  { match: /(selesai|final)/i, cls: "bg-success-lt" },
];

function setStatusBadge(el, text, map = STATUS_BADGE_MAP, fallback = "bg-primary-lt") {
  if (!el) return;
  el.textContent = text || "-";
  BADGE_BG_CLASSES.forEach((c) => el.classList.remove(c));
  el.classList.add("badge"); // jaga-jaga kalau belum ada class badge
  const rule = map.find((r) => r.match.test(text || ""));
  el.classList.add(rule ? rule.cls : fallback);
}

// ===== VIEW: isi modal detail secara aman =====
function fillViewModal(tiket) {
  const d = ticketDetails && ticketDetails[tiket];
  if (!d) return;

  const modal = document.getElementById("modal-view-laporan");
  if (!modal) return;

  const setText = (id, val) => {
    const el = modal.querySelector("#" + id);
    if (el) el.textContent = val;
  };

  setText("vw-waktu", d.waktuPengaduan || "-");

  // sumber sebagai badge sederhana
  (function () {
    const el = modal.querySelector("#vw-sumber");
    if (!el) return;
    el.textContent = d.sumber || "-";
    BADGE_BG_CLASSES.forEach((c) => el.classList.remove(c));
    el.classList.add("badge", "bg-primary"); // atau bg-primary-lt
  })();

  setText("vw-tiket", d.tiket || "-");
  setText("vw-nik", d.nik || "Tidak diisi");
  setText("vw-email", d.email || "Tidak diisi");
  setText("vw-kategori", d.kategori || "-");
  setText("vw-judul", d.judul || "-");
  setText("vw-alamat", d.alamat || "-");

  // === inilah bagian pewarnaan status ===
  setStatusBadge(modal.querySelector("#vw-status"), d.status, STATUS_BADGE_MAP, "bg-primary-lt");
  setStatusBadge(
    modal.querySelector("#vw-status-analisis"),
    d.statusAnalisis || "Pending",
    ANALISIS_BADGE_MAP,
    "bg-secondary-lt"
  );

  setText("vw-nama", d.nama || "-");
  setText("vw-nohp", d.nohp || "-");
  setText("vw-tgl-kejadian", d.tglKejadian || "Tidak diisi");
  setText("vw-detail", d.detail || "-");
  setText("vw-dokumen", d.dokumen || "-");
  setText("vw-tanggapan", d.tanggapan || "-");
  setText("vw-catatan", d.catatanDisposisi || "-");
  setText("vw-analisis", d.analisisJF || "-");

  renderActivityLog(tiket);
}

// ===== Binding tombol View (panggil saat DOM ready) =====
document.addEventListener("DOMContentLoaded", function () {
  if (window.Litepicker) {
    new Litepicker({ element: document.getElementById("ed-tgl-kejadian"), singleMode: true, format: "DD/MM/YYYY" });
  }

  const tableRoot = document.getElementById("advanced-table-laporan");
  if (!tableRoot) return;

  tableRoot.addEventListener("click", (e) => {
    const btnView = e.target.closest(".btn-view[data-ticket]");
    if (btnView) {
      fillViewModal(btnView.dataset.ticket);
    }
  });
});

function fillEditModal(tiket) {
  const d = ticketDetails[tiket];
  if (!d) {
    console.warn("detail tiket tidak ditemukan:", tiket);
    return;
  }

  // selalu ambil elemen DI DALAM modal edit
  const modal = document.getElementById("modal-edit-laporan");
  if (!modal) {
    console.warn("#modal-edit-laporan tidak ada di DOM");
    return;
  }

  // helper set value aman untuk input/textarea
  const setVal = (id, val = "") => {
    const el = modal.querySelector("#" + id);
    if (!el) {
      console.warn("elemen tidak ditemukan:", "#" + id);
      return;
    }
    el.value = val;
  };
  // helper untuk <select> (kalau value belum ada di daftar option, fallback ke option pertama)
  const setSelect = (id, val) => {
    const el = modal.querySelector("#" + id);
    if (!el) {
      console.warn("elemen select tidak ditemukan:", "#" + id);
      return;
    }
    const ok = Array.from(el.options).some((o) => o.value === val || o.text === val);
    el.value = ok ? val : el.options[0]?.value || "";
  };

  setVal("ed-tiket", d.tiket || "");
  setVal("ed-nama", d.nama || "");
  setVal("ed-nohp", d.nohp || "");
  setVal("ed-judul", d.judul || "");
  setVal("ed-alamat", d.alamat || "");
  setVal("ed-tgl-kejadian", d.tglKejadian || "");
  setSelect("ed-kategori", d.kategori);
  setSelect("ed-distribusi", d.distribusi);
  setSelect("ed-disposisi", d.disposisi);
  setSelect("ed-sumber", d.sumber);
  setSelect("ed-status", d.status);
  setVal("ed-detail", d.detail || "");
  setVal("ed-tanggapan", d.tanggapan || "");
}

document.addEventListener("DOMContentLoaded", function () {
  // delegasi klik: isi modal saat tombol dipencet
  document.getElementById("advanced-table-laporan")?.addEventListener("click", (e) => {
    const v = e.target.closest(".btn-view[data-ticket]");
    if (v) {
      fillViewModal(v.dataset.ticket);
      return;
    }
    const ed = e.target.closest(".btn-edit[data-ticket]");
    if (ed) {
      fillEditModal(ed.dataset.ticket);
      return;
    }
  });

  // submit edit: simpan ke data + update baris + refresh List.js (versi JS murni)
  document.getElementById("form-edit-laporan")?.addEventListener("submit", (e) => {
    e.preventDefault();
    const list = window.tabler_list?.["advanced-table-laporan"];
    const $ = (id) => document.getElementById(id);

    const tiket = $("#ed-tiket") ? $("#ed-tiket").value : "";
    if (!tiket) return;

    // update store detail
    const d = ticketDetails[tiket] || {};
    d.nama = $("#ed-nama")?.value.trim() || "";
    d.nohp = $("#ed-nohp")?.value.trim() || "";
    d.judul = $("#ed-judul")?.value.trim() || "";
    d.alamat = $("#ed-alamat")?.value.trim() || "";
    d.tglKejadian = $("#ed-tgl-kejadian")?.value.trim() || "";
    d.kategori = $("#ed-kategori")?.value || d.kategori;
    d.distribusi = $("#ed-distribusi")?.value || d.distribusi;
    d.disposisi = $("#ed-disposisi")?.value || d.disposisi;
    d.sumber = $("#ed-sumber")?.value || d.sumber;
    d.status = $("#ed-status")?.value || d.status;
    d.detail = $("#ed-detail")?.value.trim() || d.detail;
    d.tanggapan = $("#ed-tanggapan")?.value.trim() || d.tanggapan;
    ticketDetails[tiket] = d;

    // update cells pada baris tabel
    const rowBtn = document.querySelector(`.btn-edit[data-ticket="${tiket}"]`);
    const tr = rowBtn ? rowBtn.closest("tr") : null;
    if (tr) {
      const namaCell = tr.querySelector(".sort-nama-lengkap");
      if (namaCell) namaCell.textContent = d.nama || "-";

      const judulCell = tr.querySelector(".sort-judul");
      if (judulCell) judulCell.textContent = d.judul || "-";

      const kategoriCell = tr.querySelector(".sort-kategori");
      if (kategoriCell) kategoriCell.textContent = d.kategori || "-";

      const distribusiCell = tr.querySelector(".sort-distribusi");
      if (distribusiCell) distribusiCell.textContent = d.distribusi || "-";

      const dispoCell = tr.querySelector(".sort-disposisi");
      if (dispoCell) {
        dispoCell.textContent = d.disposisi || "-";
        dispoCell.classList.toggle("text-danger", /Belum/.test(d.disposisi || ""));
      }

      const sumberCell = tr.querySelector(".sort-sumber");
      if (sumberCell) {
        sumberCell.innerHTML = `<span class="badge bg-primary-lt">${d.sumber || "-"}</span>`;
      }

      const statusCell = tr.querySelector(".sort-status");
      if (statusCell) statusCell.textContent = d.status || "-";
    }

    // reindex + update list (biar sort/search sinkron)
    if (list) {
      list.reIndex();
      list.update();
      if (typeof toggleEmptyRow === "function") toggleEmptyRow(list);
      if (typeof updateChevronState === "function") updateChevronState(list);
    }
  });
});
