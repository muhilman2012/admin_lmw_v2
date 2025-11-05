// 1) Setup global AJAX
// <!-- pastikan jQuery sudah ada -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

// konfigurasi umum
const API_BASE = '/api'; // ganti sesuai backend kamu

$.ajaxSetup({
  headers: {
    'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') || '',
    // Authorization: 'Bearer ' + (window.AUTH_TOKEN || '')
  },
  contentType: 'application/json; charset=utf-8',
  dataType: 'json',
  timeout: 15000
});

// (opsional) loader global
$(document).ajaxStart(()=> $('#global-loading').removeClass('d-none'));
$(document).ajaxStop(()=> $('#global-loading').addClass('d-none'));

// 2) Implementasi API (jQuery)
function apiGetTicket(id){
  return $.ajax({ url: `${API_BASE}/tickets/${encodeURIComponent(id)}`, method: 'GET' });
}
function apiListInstansi(){
  return $.ajax({ url: `${API_BASE}/instansi`, method: 'GET' });
}
function apiSaveEdit(payload){
  return $.ajax({
    url: `${API_BASE}/tickets/${encodeURIComponent(payload.tiket)}`,
    method: 'PUT',
    data: JSON.stringify(payload)
  });
}
function apiForward({ tiket, instansi, keterangan, anonim, data }){
  return $.ajax({
    url: `${API_BASE}/tickets/${encodeURIComponent(tiket)}/forward`,
    method: 'POST',
    data: JSON.stringify({ instansi, keterangan, anonim, data })
  });
}

// 3) Pakai di alur kamu
// buka modal Edit (load detail + daftar instansi)
let editReq = null; // supaya bisa abort kalau user klik cepat
async function openEditModal(ticketId){
  state.currentId = ticketId;

  if (editReq && editReq.abort) try { editReq.abort(); } catch(e){}

  const reqDetail = apiGetTicket(ticketId);
  const reqInst   = state.instansiList.length ? $.Deferred().resolve(state.instansiList) : apiListInstansi();
  editReq = reqDetail;

  $.when(reqDetail, reqInst).done((detailRes, instansiRes) => {
    // catatan: $.when memberi [data, textStatus, jqXHR] per argumen
    const detail   = Array.isArray(detailRes)   ? detailRes[0]   : detailRes;
    const instansi = Array.isArray(instansiRes) ? instansiRes[0] : instansiRes;

    state.current = { ...detail };
    state.instansiList = instansi || [];

    fillEditModal(ticketId);

    const $sel = $('#modal-edit-laporan #ed-distribusi').empty();
    state.instansiList.forEach(v => $sel.append($('<option>', { value: v, text: v })));
    if (state.current.distribusi) $sel.val(state.current.distribusi);

  }).fail((xhr) => {
    const msg = xhr?.responseJSON?.message || 'Tidak bisa memuat data.';
    toastTabler({ title:'Gagal', variant:'danger', message: msg });
  }).always(() => { editReq = null; });
}

// submit Edit → simpan
$('#form-edit-laporan').on('submit', function(e){
  e.preventDefault();
  if (!state.currentId || !state.current) return;
  const payload = { ...state.current, tiket: state.currentId };

  apiSaveEdit(payload)
    .done(() => {
      // update tabel seperti yang sudah kamu lakukan
      const tr = $(`.btn-edit[data-ticket="${state.currentId}"]`).closest('tr');
      tr.find('.sort-nama-lengkap').text(payload.nama || '-');
      tr.find('.sort-judul').text(payload.judul || '-');
      tr.find('.sort-kategori').text(payload.kategori || '-');
      tr.find('.sort-distribusi').text(payload.distribusi || '-');
      tr.find('.sort-disposisi').text(payload.disposisi || '-')
        .toggleClass('text-danger', /Belum/i.test(payload.disposisi||''));
      tr.find('.sort-sumber').html(`<span class="badge bg-primary-lt">${payload.sumber || '-'}</span>`);
      tr.find('.sort-status').text(payload.status || '-');

      window.tabler_list?.['advanced-table-laporan']?.reIndex();
      window.tabler_list?.['advanced-table-laporan']?.update();

      toastTabler({ title:'Perubahan disimpan', variant:'success', message:`Tiket ${payload.tiket} berhasil diperbarui.` });
    })
    .fail((xhr) => {
      toastTabler({ title:'Gagal', variant:'danger', message: xhr?.responseJSON?.message || 'Simpan gagal.' });
    });
});

// buka modal Forward (isi opsi & default dari Edit)
$('#btn-forward-open').on('click', function(){
  const $fw = $('#fw-instansi').empty().append('<option value="" disabled selected>Pilih Instansi</option>');
  const list = state.instansiList.length ? state.instansiList : ['deputi_1','deputi_2','deputi_3'];
  list.forEach(v => $fw.append($('<option>', {value:v, text:v})));
  if (state.current?.distribusi) $fw.val(state.current.distribusi);
  $('#fw-keterangan').val(''); $('#fw-anon').prop('checked', false);
});

// submit Forward → kirim
$('#form-forward-instansi').on('submit', function(e){
  e.preventDefault();
  const tiket    = state.currentId;
  const instansi = $('#fw-instansi').val() || '';
  const ket      = String($('#fw-keterangan').val() || '').trim();
  const anonim   = $('#fw-anon').is(':checked');

  if (!tiket || !instansi){
    toastTabler({ title:'Lengkapi dulu', variant:'warning', message:'Pilih instansi tujuan.' });
    return;
  }

  // payload dari state terkini (sudah sinkron oleh listener input)
  const data = { ...state.current, tiket };
  if (anonim) ['nama','nik','nohp','email','alamat'].forEach(k => data[k] = '');

  apiForward({ tiket, instansi, keterangan: ket, anonim, data })
    .done(() => {
      // update UI
      const tr = $(`.btn-edit[data-ticket="${tiket}"]`).closest('tr');
      tr.find('.sort-distribusi').text(instansi);
      tr.find('.sort-disposisi').text('Sudah terdisposisi').removeClass('text-danger');

      window.tabler_list?.['advanced-table-laporan']?.reIndex();
      window.tabler_list?.['advanced-table-laporan']?.update();

      toastTabler({ title:'Diteruskan', variant:'success', message:`Tiket ${tiket} diteruskan ke ${instansi}${anonim?' (anonim)':''}.` });
    })
    .fail((xhr) => {
      toastTabler({ title:'Gagal', variant:'danger', message: xhr?.responseJSON?.message || 'Gagal mengirim.' });
    });
});

// 4) Sinkron form Edit → state (supaya forward pakai nilai terbaru)
// sudah kamu punya versi vanilla; ini versi jQuery (boleh pilih salah satu)
$(document).on('input change', '#modal-edit-laporan [id^="ed-"]', function(){
  if (!state.current) return;
  const map = {
    'ed-nama':'nama','ed-nohp':'nohp','ed-judul':'judul','ed-alamat':'alamat','ed-tgl-kejadian':'tglKejadian',
    'ed-kategori':'kategori','ed-distribusi':'distribusi','ed-disposisi':'disposisi',
    'ed-sumber':'sumber','ed-status':'status','ed-detail':'detail','ed-tanggapan':'tanggapan'
  };
  const key = map[this.id];
  if (key) state.current[key] = $(this).val();
});


// Catatan:
// Untuk file upload, kirim FormData dan set processData:false, contentType:false.
// Untuk cancel request, simpan const req = $.ajax(...); req.abort() (lihat editReq di atas).
// Kalau backend butuh credentials cookie, tambahkan xhrFields:{ withCredentials:true }.
// Ini langsung plug-and-play ke struktur yang sudah kamu bangun 👍