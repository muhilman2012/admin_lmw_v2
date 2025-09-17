/* laporan-dummy-data.js
   Generator data dummy untuk tabel Laporan
   - window.laporanData       => array data
   - window.generateDummyLaporan(count) => fungsi pembuat ulang
   - window.statusToColor(s)  => map status -> kelas badge
*/
(function () {
  // Map status -> warna badge (samakan dengan style kamu)
  const STATUS_COLORS = {
    "Proses verifikasi dan telaah": "bg-blue text-blue-fg",
    Ditolak: "bg-red text-red-fg",
    Pending: "bg-yellow text-yellow-fg",
    "Penanganan Selesai": "bg-green text-green-fg",
    "Menunggu kelengkapan data dari pelapor": "bg-yellow text-yellow-fg",
    "Diteruskan kepada instansi yang berwenang": "bg-info text-info-fg",
  };
  function statusToColor(status) {
    return STATUS_COLORS[status] || "bg-secondary";
  }

  // Pool nilai
  const first = [
    "Budi",
    "Siti",
    "Agus",
    "Rina",
    "Dewi",
    "Ahmad",
    "Tono",
    "Lina",
    "Hendra",
    "Nina",
    "Rudi",
    "Intan",
    "Yusuf",
    "Tari",
    "Andi",
    "Rama",
    "Citra",
    "Dian",
    "Eka",
    "Fajar",
    "Gita",
    "Hari",
    "Indra",
    "Joko",
    "Kiki",
    "Lukman",
    "Maya",
    "Nanda",
    "Oki",
    "Putri",
    "Rizky",
    "Salsa",
    "Taufik",
    "Uli",
    "Vina",
    "Wahyu",
    "Yuni",
    "Zaki",
  ];
  const last = [
    "Santoso",
    "Wijaya",
    "Saputra",
    "Pratama",
    "Herlina",
    "Pratiwi",
    "Setiawan",
    "Kusuma",
    "Hidayat",
    "Maulana",
    "Suryani",
    "Fauzi",
    "Firmansyah",
    "Ramadhan",
    "Utami",
    "Nugroho",
    "Ananda",
    "Mahardika",
    "Putra",
    "Putri",
  ];
  const judulA = [
    "Bantuan infrastruktur Jalan Desa",
    "Perbaikan Drainase RW",
    "Sengketa Batas Tanah",
    "Keberatan Proses Perizinan",
    "Bantuan Renovasi Rumah",
    "Penerangan Jalan Umum",
    "Perbaikan Jembatan Lingkungan",
    "Kepastian Operasional Perusahaan",
    "Bantuan Biaya Berobat",
    "Pelayanan Administrasi Kependudukan",
  ];
  const kategoriA = ["Pekerjaan Umum dan Penataan", "Politik dan Hukum", "Sosial dan Kesejahteraan", "Pertanahan"];
  const distribusiA = ["deputi_1", "deputi_2", "deputi_3"];
  const disposisiA = ["Belum terdisposisi", "Sudah terdisposisi"];
  const sumberA = ["TM", "Web", "Email"];
  const statusA = [
    "Proses verifikasi dan telaah",
    "Penanganan Selesai",
    "Menunggu kelengkapan data dari pelapor",
    "Diteruskan kepada instansi yang berwenang",
    "Ditolak",
    "Pending",
  ];
  const detailA = [
    "pelapor menyampaikan kondisi jalan di daerah tempat kelahirannya saat ini sangat buruk dan banjir ketika musim hujan. alamat desa: sei kubung dusun iv, kel/desa sei penggantungan, kec. panai hilir, kab. labuhan batu, sumatera utara. letaknya 327 km dari kota medan, mayoritas penduduk petani. permohonan agar pemerintah memperbaiki jalan desa tersebut dan memperhatikan kesejahteraan penduduk desa tersebut. catatan: data dukung berupa softcopy dan hardcopy.",
    "Pelaporan terkait kasus penipuan tahun 2012 sudah dilaporkan di Polres Metro Jakarta Utara, namun belum ada perkembangan kasusnya. berkas sudah masuk mailingroom dan sedang proses tindaklanjut di Deputi 3, Asdep WKPK.",
    "Saya seorang karyawan yang bekerja di perusahaan retail, sudah bekerja kurang lebih 8 tahun 9 bulan status karyawan tetap, dan bulan april 2024 kemaren ada PHK di tempat saya bekerja dan saya salah satu dari sekian banyak yg di PHK, untuk di daerah saya ada sekitar 30 orang yang di PHK dan dijanjikan pesangon, dan kami sudah TTD surat pesangonnya tetapi sampai saat ini belum juga ada kejelasan, setiap perusahaan di tanya jawabannya selalu sabar, alasan PHK katanya untuk efesiensi. Saya dan beberapa teman juga sudah melaporkan ke Disnaker tetapi belum ada kabar mengenai laporan tersebut. Mohon bantuannya pak",
  ];

  // Util
  const pick = (arr, idx) => arr[idx % arr.length];
  const randInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
  const pad2 = (n) => (n < 10 ? "0" + n : "" + n);
  function dateToDDMMYYYY(d) {
    const mm = pad2(d.getMonth() + 1);
    const dd = pad2(d.getDate());
    const yyyy = d.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
  }
  function randomDateWithin(daysBack = 45) {
    const now = new Date();
    const d = new Date(now);
    d.setDate(now.getDate() - randInt(0, daysBack));
    return d;
  }
  function tiketFromIndex(i) {
    // 7-digit seperti contoh: 1974xxx
    return "197" + String(4000 + i).padStart(4, "0");
  }

  function generateDummyLaporan(count = 300) {
    const rows = [];
    for (let i = 1; i <= count; i++) {
      const fn = pick(first, i * 13);
      const ln = pick(last, i * 7);
      const nama = `${fn} ${ln}`;
      const judul = pick(judulA, i * 5);
      const kategori = pick(kategoriA, i * 3);
      const distribusi = pick(distribusiA, i * 11);
      const disposisi = pick(disposisiA, i * 17);
      const sumber = pick(sumberA, i * 19);
      const status = pick(statusA, i * 23);
      const detail = pick(detailA, i * 2);
      const dikirim = dateToDDMMYYYY(randomDateWithin(60));
      const nohp = "0821" + String(300000000 + Math.floor(Math.random() * 699999999)).slice(0, 7);
      const alamat = "puri lestari blok f no. 48, kel. sukajaya, cibitung, kab. bekasi, jawa barat.";
      const nik = "1210194906050000";

      rows.push({
        no: i,
        tiket: tiketFromIndex(i),
        nama,
        nohp,
        nik,
        alamat,
        judul,
        kategori,
        distribusi,
        disposisi,
        sumber,
        status,
        dikirim,
        detail,
        petugas_analis: "ANALIS 1, S.Kom",
        catatan_disposisi:
          "Ini adalah contoh catatan disposisi yang lumayan panjang ya, buat gambaran aja kalo modelannya agak panjang gimana.",
      });
    }
    return rows;
  }

  // Expose ke global
  window.statusToColor = statusToColor;
  window.generateDummyLaporan = generateDummyLaporan;
  window.laporanData = generateDummyLaporan(300);
})();
