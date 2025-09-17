/* pengadu-dummy-data.js */
/* Hanya bertugas membuat data dummy dan menaruhnya ke window.pengaduData */

(function () {
  function generateDummyPengadu(count = 300) {
    const first = ["Budi","Siti","Agus","Rina","Dewi","Ahmad","Tono","Lina","Hendra","Nina","Rudi","Intan","Yusuf","Tari","Andi","Rama","Citra","Dian","Eka","Fajar","Gita","Hari","Indra","Joko","Kiki","Lukman","Maya","Nanda","Oki","Putri","Rizky","Salsa","Taufik","Uli","Vina","Wahyu","Yuni","Zaki"];
    const last  = ["Santoso","Wijaya","Saputra","Pratama","Herlina","Pratiwi","Setiawan","Kusuma","Hidayat","Maulana","Suryani","Fauzi","Firmansyah","Ramadhan","Utami","Nugroho","Ananda","Mahardika","Putra","Putri"];
    const streets = ["Jl. Merdeka","Jl. Sudirman","Jl. Thamrin","Jl. Diponegoro","Jl. Gajah Mada","Jl. Ahmad Yani","Jl. S Parman","Jl. Kenanga","Jl. Melati","Jl. Anggrek","Jl. Beringin","Jl. Mawar","Jl. Cempaka","Jl. Flamboyan","Jl. Kelapa","Jl. Cemara"];
    const cities  = ["Jakarta","Bandung","Surabaya","Semarang","Yogyakarta","Medan","Makassar","Denpasar","Palembang","Bekasi","Depok","Tangerang"];

    const data = [];
    for (let i = 1; i <= count; i++) {
      const fn = first[(i * 13) % first.length];
      const ln = last[(i * 7) % last.length];
      const nama = `${fn} ${ln}`;
      const nik = "33" + String(Math.floor(10 ** 14 + Math.random() * 9 * 10 ** 13)).slice(0, 14);
      const nohp = "08" + String(Math.floor(100000000 + Math.random() * 900000000));
      const email = `${fn}.${ln}${i}@example.com`.toLowerCase().replace(/\s+/g, "");
      const alamat = `${streets[i % streets.length]} No. ${((i * 3) % 200) + 1}, ${cities[(i * 5) % cities.length]}`;

      data.push({ nomor: i, namaLengkap: nama, nik, nohp, email, alamat });
    }
    return data;
  }

  // Expose ke global
  window.pengaduData = generateDummyPengadu(300);
  window.generateDummyPengadu = generateDummyPengadu; // kalau nanti mau regenerate
})();
