// view-laporan.js
(function () {
  const $ = (s, r = document) => r.querySelector(s);

  function q(name) {
    return new URL(location.href).searchParams.get(name);
  }
  function text(id, val, fallback = "-") {
    const el = $(id);
    if (el) el.textContent = (val && String(val).trim()) || fallback;
  }
  function badge(id, label, cls) {
    const el = $(id);
    if (!el) return;
    el.textContent = label || "-";
    el.className = "badge " + (cls || "bg-secondary");
  }

  // mapping warna status utama (pakai statusToColor kalau sudah ada)
  function statusColor(status) {
    return window.statusToColor ? window.statusToColor(status) : "bg-secondary";
  }
  // mapping warna status analisis
  function analisisColor(s) {
    const key = String(s || "").toLowerCase();
    if (key.includes("selesai") || key.includes("done")) return "bg-green";
    if (key.includes("proses") || key.includes("progress")) return "bg-blue-lt";
    if (key.includes("ditolak") || key.includes("tolak")) return "bg-red";
    if (key.includes("revisi")) return "bg-yellow-lt";
    return "bg-secondary-lt";
  }

  function linkifyList(files) {
    if (!files) return "-";
    // dukung string tunggal, array string, atau array object {name,url}
    if (typeof files === "string") return `<a href="${files}" target="_blank" rel="noopener">${files}</a>`;
    if (Array.isArray(files) && files.length) {
      return files
        .map((f, i) => {
          if (typeof f === "string") return `<a href="${f}" target="_blank" rel="noopener">Lampiran ${i + 1}</a>`;
          const name = f.name || `Lampiran ${i + 1}`;
          const url = f.url || "#";
          return `<a href="${url}" target="_blank" rel="noopener">${name}</a>`;
        })
        .join(", ");
    }
    return "-";
  }

  // ====== UTIL DOKUMEN: dummy + renderer + download ======
  function makeDummyFiles() {
    return [
      { name: "KTP_Pelapor.pdf", type: "application/pdf" },
      {
        name: "Surat_Permohonan.docx",
        type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      },
      { name: "Foto_Lokasi.jpg", type: "image/jpeg" },
    ];
  }

  // Buat URL blob untuk file dummy (agar bisa diunduh)
  function makeBlobUrl(type, content = "Dummy content for preview/download") {
    const blob = new Blob([content], { type: type || "application/octet-stream" });
    return URL.createObjectURL(blob);
  }

  // Normalisasi input dokumen dari berbagai bentuk:
  // - string URL → [{name:"Lampiran 1", url:"..."}]
  // - array string → [{name:"Lampiran n", url:"..."}]
  // - array object {name,url} → pakai apa adanya
  function normalizeDocs(files) {
    if (!files) return [];
    if (typeof files === "string") return [{ name: "Lampiran 1", url: files }];
    if (Array.isArray(files)) {
      return files.map((f, i) => {
        if (typeof f === "string") return { name: `Lampiran ${i + 1}`, url: f };
        return { name: f.name || `Lampiran ${i + 1}`, url: f.url, type: f.type };
      });
    }
    // object tunggal
    return [{ name: files.name || "Lampiran", url: files.url, type: files.type }];
  }

  /* ================== PREVIEW DOKUMEN (Tabler) ================== */
  // Dummy list bila data kosong
  function makeDummyFiles() {
    return [
      { name: "KTP_Pelapor.pdf", type: "application/pdf" },
      {
        name: "Surat_Permohonan.docx",
        type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      },
      { name: "Foto_Lokasi.jpg", type: "image/jpeg" },
    ];
  }
  function makeBlobUrl(type, content = "Dummy content for preview/download") {
    const blob = new Blob([content], { type: type || "application/octet-stream" });
    return URL.createObjectURL(blob);
  }
  function extToMime(name = "") {
    const ext = name.split(".").pop().toLowerCase();
    const map = {
      pdf: "application/pdf",
      jpg: "image/jpeg",
      jpeg: "image/jpeg",
      png: "image/png",
      webp: "image/webp",
      gif: "image/gif",
      txt: "text/plain",
      csv: "text/csv",
      json: "application/json",
      docx: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    };
    return map[ext] || "application/octet-stream";
  }
  function normalizeDocs(files) {
    if (!files) return [];
    if (typeof files === "string") return [{ name: "Lampiran 1", url: files, type: extToMime(files) }];
    if (Array.isArray(files)) {
      return files.map((f, i) => {
        if (typeof f === "string") return { name: `Lampiran ${i + 1}`, url: f, type: extToMime(f) };
        return { name: f.name || `Lampiran ${i + 1}`, url: f.url, type: f.type || extToMime(f.name || "") };
      });
    }
    // object tunggal
    return [{ name: files.name || "Lampiran", url: files.url, type: files.type || extToMime(files.name || "") }];
  }

  // Sisipkan modal preview sekali saja
  function ensurePreviewModal() {
    if (document.getElementById("modal-file-preview")) return;
    const html = `
  <div class="modal fade" id="modal-file-preview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-file-preview-title">Preview</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="modal-file-preview-body" class="text-center">
            <div class="my-4"><span class="spinner-border" role="status"></span></div>
          </div>
        </div>
        <div class="modal-footer">
          <a id="modal-file-preview-download" class="btn btn-outline-secondary" href="#" download>Unduh</a>
          <button class="btn" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>`;
    document.body.insertAdjacentHTML("beforeend", html);
  }
  function setPreviewBody(html) {
    const body = document.getElementById("modal-file-preview-body");
    if (body) body.innerHTML = html;
  }
  function escapeHtml(s = "") {
    return String(s).replace(
      /[&<>"']/g,
      (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m])
    );
  }

  function openModalSafe(id) {
    const el = document.getElementById(id);
    if (!el) return;
    if (window.bootstrap?.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(el).show();
      return;
    }
    // Fallback manual (tanpa bootstrap)
    el.classList.add("show");
    el.style.display = "block";
    el.removeAttribute("aria-hidden");
    el.setAttribute("aria-modal", "true");
    document.body.classList.add("modal-open");

    // backdrop
    if (!document.querySelector(".modal-backdrop")) {
      const bd = document.createElement("div");
      bd.className = "modal-backdrop fade show";
      document.body.appendChild(bd);
    }
  }

  function closeModalSafe(id) {
    const el = document.getElementById(id);
    if (!el) return;
    if (window.bootstrap?.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(el).hide();
      return;
    }
    // Fallback manual
    el.classList.remove("show");
    el.style.display = "none";
    el.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    document.querySelector(".modal-backdrop")?.remove();
  }

  // Tutup modal fallback saat tombol close ditekan
  document.addEventListener("click", (e) => {
    if (
      e.target.closest("#modal-file-preview .btn-close") ||
      e.target.closest('#modal-file-preview [data-bs-dismiss="modal"]')
    ) {
      closeModalSafe("modal-file-preview");
    }
  });

  // Render konten preview ke modal berdasarkan MIME
  async function previewFile(doc) {
    ensurePreviewModal();
    const titleEl = document.getElementById("modal-file-preview-title");
    const dlEl = document.getElementById("modal-file-preview-download");
    if (titleEl) titleEl.textContent = doc.name || "Preview";
    if (dlEl) {
      dlEl.href = doc.url;
      dlEl.download = doc.name || "file";
    }

    // spinner
    setPreviewBody(`<div class="my-4"><span class="spinner-border" role="status"></span></div>`);

    const type = (doc.type || "").toLowerCase();

    // Gambar
    if (type.startsWith("image/")) {
      setPreviewBody(`<img src="${doc.url}" alt="${escapeHtml(doc.name)}" class="img-fluid rounded" />`);
    }
    // PDF
    else if (type === "application/pdf") {
      setPreviewBody(`<iframe src="${doc.url}" style="width:100%;height:70vh;border:0;border-radius:.5rem;"></iframe>`);
    }
    // Text/JSON/CSV: tampilkan isi sebagai <pre>
    else if (type.startsWith("text/") || type === "application/json") {
      try {
        const res = await fetch(doc.url);
        const text = await res.text();
        setPreviewBody(
          `<pre class="code" style="max-height:70vh; overflow:auto; text-align:left;">${escapeHtml(text)}</pre>`
        );
      } catch {
        setPreviewBody(`<div class="text-muted">Tidak bisa memuat pratinjau konten.</div>`);
      }
    }
    // Lainnya (docx, dll) — tidak ada preview native
    else {
      setPreviewBody(`
      <div class="text-center my-4">
        <i class="ti ti-file-description" style="font-size:2rem;"></i>
        <div class="mt-2"><strong>Preview tidak tersedia untuk tipe ini.</strong></div>
        <div class="text-secondary">Gunakan tombol <em>Unduh</em> untuk membuka di aplikasi terkait.</div>
      </div>`);
    }

    // buka modal
    openModalSafe("modal-file-preview");
  }

  // Render list: nama file + tombol Preview
  function renderDokumenList(containerSelector, files) {
    const root = document.querySelector(containerSelector);
    if (!root) return;

    let docs = normalizeDocs(files);
    if (!docs.length) {
      // pakai dummy saat kosong
      docs = makeDummyFiles().map((d) => ({
        name: d.name,
        type: d.type,
        url: makeBlobUrl(d.type, `This is a dummy file: ${d.name}`),
      }));
    } else {
      // pastikan punya url; kalau tidak ada → buat blob dummy agar tetap bisa dipreview
      docs = docs.map((d, i) => ({
        name: d.name || `Lampiran ${i + 1}`,
        type: d.type || extToMime(d.name || ""),
        url:
          d.url ||
          makeBlobUrl(d.type || extToMime(d.name || ""), `This is a dummy file: ${d.name || `Lampiran ${i + 1}`}`),
      }));
    }

    root.innerHTML = "";
    docs.forEach((d) => {
      const row = document.createElement("div");
      row.className = "mb-2 d-flex align-items-center gap-2 flex-wrap";
      row.innerHTML = `
      <span><i class="ti ti-file me-2"></i> ${escapeHtml(d.name)}</span>
      <button type="button" class="btn btn-sm btn-outline-primary btn-preview">
        <i class="ti ti-eye"></i> Preview
      </button>
    `;
      row.querySelector(".btn-preview").addEventListener("click", () => previewFile(d));
      root.appendChild(row);
    });
  }
  /* ============================================================ */

  function fillView(d) {
    // Header ringkas
    text("#vw-tiket", d.tiket);

    // DL #1
    text("#vw-tiket-dup", d.tiket);
    text("#vw-nik", d.nik, "Tidak diisi");
    text("#vw-email", d.email, "Tidak diisi");
    const elKat = $("#vw-kategori");
    if (elKat) elKat.textContent = d.kategori || "-";
    text("#vw-judul", d.judul);
    text("#vw-alamat", d.alamat);
    badge("#vw-status-badge", d.status, statusColor(d.status));
    text("#vw-distribusi", d.distribusi);

    // Status analisis (field di data: `statusAnalisis` / `status_analis` / `status_analisis`)
    const sAnalisis = d.statusAnalisis || d.status_analis || d.status_analisis || "Pending";
    badge("#vw-status-analisis", sAnalisis, analisisColor(sAnalisis));

    // DL #2
    text("#vw-nama", d.nama);
    text("#vw-nohp", d.nohp);
    text("#vw-tgl-kejadian", d.tglKejadian || d.tanggal_kejadian || "Tidak diisi");
    text("#vw-detail", d.detail || d.uraian || d.ringkasan || "-");
    // const dok = $("#vw-dokumen"); if (dok) dok.innerHTML = linkifyList(d.dokumen || d.lampiran);
    renderDokumenList("#vw-dokumen", d.dokumen || d.lampiran);
    text("#vw-tanggapan", d.tanggapan || d.tindak_lanjut || "-");
    text("#vw-catatan", d.catatan || d.catatan_disposisi || "-");
    text("#vw-analisis", d.analisis || d.analisis_jf || "-");
    text("#vw-petugas-analis", d.petugas_analis || "-");

    // Log sederhana (kalau tidak ada, pakai fallback)
    const logs =
      Array.isArray(d.logs) && d.logs.length
        ? d.logs
        : [
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
            { tgl: d.dikirim || "-", act: "Pengaduan dikirim", by: d.nama || "Pengguna" },
            { tgl: d.dikirim || "-", act: "Proses verifikasi & telaah", by: "Admin" },
          ];
    const tb = $("#vw-logs");
    if (tb) {
      tb.innerHTML = logs
        .map(
          (l) => `
        <tr>
          <td>${l.tgl || "-"}</td>
          <td>${l.act || "-"}</td>
          <td>${l.by || "-"}</td>
        </tr>`
        )
        .join("");
    }

    // tombol ke edit
    const editLink = $("#vw-edit-link");
    if (editLink) editLink.href = `edit-laporan-pengaduan.html?tiket=${encodeURIComponent(d.tiket || "")}`;
  }

  document.addEventListener("DOMContentLoaded", () => {
    const tiket = q("tiket");
    const data = (window.laporanData || []).find((x) => String(x.tiket) === String(tiket));
    fillView(data || {});
  });
})();
