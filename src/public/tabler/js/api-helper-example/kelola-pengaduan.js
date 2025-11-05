// ===== STATE PUSAT =====
const state = {
  currentId: null,        // tiket yang sedang dibuka
  current: null,          // detail tiket saat ini
  instansiList: [],       // daftar instansi dari API
};

// ===== API DUMMY (ganti dengan fetch asli) =====
async function apiGetTicket(id) {
  // return await fetch(`/api/tickets/${id}`).then(r=>r.json());
  return ticketDetails[id]; // sementara pakai store yang sudah ada
}
async function apiListInstansi() {
  // return await fetch('/api/instansi').then(r=>r.json());
  return ['deputi_1','deputi_2','deputi_3']; // contoh
}
async function apiSaveEdit(payload) {
  // return await fetch(`/api/tickets/${payload.tiket}`, {method:'PUT', body:JSON.stringify(payload)});
  return { ok: true };
}
async function apiForward(payload) {
  // return await fetch(`/api/tickets/${payload.tiket}/forward`, {method:'POST', body:JSON.stringify(payload)});
  return { ok: true };
}

// ===== BUKA MODAL EDIT (dari tombol .btn-edit) =====
async function openEditModal(ticketId) {
  state.currentId = ticketId;
  // load paralel: detail tiket + list instansi
  const [detail, instansi] = await Promise.all([
    apiGetTicket(ticketId),
    state.instansiList.length ? [state.instansiList] : apiListInstansi(),
  ]);
  state.current = { ...detail };
  state.instansiList = Array.isArray(instansi) ? instansi : instansi[0];

  // isi form Edit dari state
  fillEditModal(ticketId); // fungsi aman yang sudah kamu pakai

  // (opsional) pasang opsi distribusi dari instansiList
  const sel = document.querySelector('#modal-edit-laporan #ed-distribusi');
  if (sel && state.instansiList.length) {
    sel.innerHTML = state.instansiList.map(v => `<option>${v}</option>`).join('');
    sel.value = state.current.distribusi || state.instansiList[0];
  }
}

// binding tombol Edit (sekali saja)
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('advanced-table-laporan')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-edit[data-ticket]');
    if (btn) openEditModal(btn.dataset.ticket);
  });
});

// ===== SINKRON FORM EDIT -> STATE (supaya forward ikut nilai terbaru) =====
(function bindEditFormToState(){
  const ids = ['ed-nama','ed-nohp','ed-judul','ed-alamat','ed-tgl-kejadian',
               'ed-kategori','ed-distribusi','ed-disposisi','ed-sumber','ed-status','ed-detail','ed-tanggapan'];
  document.addEventListener('input', (e) => {
    const id = e.target?.id;
    if (!id || !ids.includes(id) || !state.current) return;
    const map = {
      'ed-nama':'nama','ed-nohp':'nohp','ed-judul':'judul','ed-alamat':'alamat','ed-tgl-kejadian':'tglKejadian',
      'ed-kategori':'kategori','ed-distribusi':'distribusi','ed-disposisi':'disposisi',
      'ed-sumber':'sumber','ed-status':'status','ed-detail':'detail','ed-tanggapan':'tanggapan'
    };
    state.current[map[id]] = e.target.value;
  });
})();

// ===== SUBMIT EDIT -> API =====
document.getElementById('form-edit-laporan')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!state.current) return;
  const payload = { ...state.current, tiket: state.currentId };

  const res = await apiSaveEdit(payload);
  if (res?.ok) {
    // update tabel (pakai logikamu yang sudah ada)
    document.querySelector(`.btn-edit[data-ticket="${state.currentId}"]`)?.closest('tr')?.querySelector('.sort-nama-lengkap')?.textContent = payload.nama || '-';
    // ...update sel lain seperti sebelumnya...
    window.tabler_list?.['advanced-table-laporan']?.reIndex();
    window.tabler_list?.['advanced-table-laporan']?.update();

    toastTabler({ title: 'Perubahan disimpan', variant: 'success', message: `Tiket ${payload.tiket} berhasil diperbarui.` });
  } else {
    toastTabler({ title: 'Gagal', variant: 'danger', message: 'Tidak bisa menyimpan perubahan.' });
  }
});

// ===== BUKA MODAL FORWARD =====
document.getElementById('btn-forward-open')?.addEventListener('click', async () => {
  // pastikan list instansi terisi
  if (!state.instansiList.length) state.instansiList = await apiListInstansi();

  // isi select instansi
  const fwSel = document.getElementById('fw-instansi');
  if (fwSel) {
    fwSel.innerHTML = '<option value="" disabled selected>Pilih Instansi</option>' +
      state.instansiList.map(v=>`<option value="${v}">${v}</option>`).join('');
    fwSel.value = state.current?.distribusi || '';
  }

  // kosongkan keterangan & anonim
  const fwKet = document.getElementById('fw-keterangan'); if (fwKet) fwKet.value = '';
  const fwAnon = document.getElementById('fw-anon'); if (fwAnon) fwAnon.checked = false;
});

// ===== SUBMIT FORWARD -> API =====
document.getElementById('form-forward-instansi')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const tiket   = state.currentId;
  const instansi= document.getElementById('fw-instansi')?.value || '';
  const ket     = document.getElementById('fw-keterangan')?.value?.trim() || '';
  const anonim  = document.getElementById('fw-anon')?.checked || false;

  if (!tiket || !instansi) {
    toastTabler({ title:'Lengkapi dulu', variant:'warning', message:'Silakan pilih instansi tujuan.' });
    return;
  }

  // ambil data TERBARU (sudah sinkron dari form Edit via state)
  let data = { ...state.current, tiket };
  if (anonim) ['nama','nik','nohp','email','alamat'].forEach(k => data[k] = ''); // scrub PII

  const res = await apiForward({ tiket, instansi, keterangan: ket, anonim, data });
  if (res?.ok) {
    // update state + tabel
    state.current.distribusi = instansi;
    state.current.disposisi = 'Sudah terdisposisi';
    const trBtn = document.querySelector(`.btn-edit[data-ticket="${tiket}"]`);
    const tr = trBtn?.closest('tr');
    tr?.querySelector('.sort-distribusi')?.textContent = instansi;
    const dispo = tr?.querySelector('.sort-disposisi');
    if (dispo){ dispo.textContent = 'Sudah terdisposisi'; dispo.classList.remove('text-danger'); }
    window.tabler_list?.['advanced-table-laporan']?.reIndex();
    window.tabler_list?.['advanced-table-laporan']?.update();

    toastTabler({ title:'Diteruskan', variant:'success', message:`Tiket ${tiket} diteruskan ke ${instansi}${anonim?' (anonim)':''}.` });
  } else {
    toastTabler({ title:'Gagal', variant:'danger', message:'Tidak bisa meneruskan ke instansi.' });
  }
});
