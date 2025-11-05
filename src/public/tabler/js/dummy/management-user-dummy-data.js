(function () {
  /* ================== DUMMY DATA ================== */
  function generateDummyUser(count = 30) {
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
    const role = [
      "superadmin",
      "deputi_1",
      "deputi_2",
      "deputi_3",
      "deputi_4",
      "analis_1",
      "analis_2",
      "analis_3",
      "analis_4",
    ];
    const jabatan = [
      "Superadmin",
      "Analis",
      "Kepala Bagian Dukungan Administrasi",
      "Deputi Bidang Dukungan Kebijakan Perekonomian, Pariwisata, dan Transformasi Digital",
    ];
    const unit = ["Admin"];
    const deputi = ["Admin"];

    const data = [];
    for (let i = 1; i <= count; i++) {
      const fn = first[(i * 13) % first.length];
      const ln = last[(i * 7) % last.length];
      const nama = `${fn} ${ln}`;
      const email = `${fn}.${ln}${i}@example.com`.toLowerCase().replace(/\s+/g, "");
      const jabatans = jabatan[i % jabatan.length];
      const roles = role[i % role.length];
      const units = unit[i % unit.length];
      const deputis = deputi[i % deputi.length];

      data.push({ id: i, nama, email, role: roles, jabatan: jabatans, unit: units, deputi: deputis });
    }
    return data;
  }

  // Expose ke global
  window.userData = generateDummyUser(300);
  window.generateDummyUser = generateDummyUser; // kalau nanti mau regenerate
})();
