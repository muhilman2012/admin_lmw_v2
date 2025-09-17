function toastTabler({
  title = 'Berhasil',
  message = '',
  variant = 'success', // success | info | warning | danger
  delay = 3000,
  placement = '#toast-placement'
} = {}) {
  const container = document.querySelector(placement) || document.body;

  const ICONS = {
    success: 'ti ti-check',
    info: 'ti ti-info-circle',
    warning: 'ti ti-alert-triangle',
    danger: 'ti ti-alert-circle'
  };
  const COLOR = {
    success: 'text-success',
    info: 'text-azure',
    warning: 'text-orange',
    danger: 'text-danger'
  };

  const el = document.createElement('div');
  el.className = 'toast fade';
  el.setAttribute('role', 'alert');
  el.setAttribute('aria-live', 'assertive');
  el.setAttribute('aria-atomic', 'true');
  el.innerHTML = `
    <div class="toast-header">
      <i class="${ICONS[variant] || ICONS.info} ${COLOR[variant] || COLOR.info} me-2"></i>
      <strong class="me-auto">${title}</strong>
      <small class="text-muted">baru saja</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">${message}</div>
  `;

  container.appendChild(el);

  // Pakai Bootstrap kalau ada; kalau tidak, fallback manual
  if (window.bootstrap && window.bootstrap.Toast) {
    const t = new bootstrap.Toast(el, { delay, autohide: true });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
  } else {
    // Fallback sederhana: tampilkan & auto-remove
    el.classList.add('show');
    const close = () => { el.classList.remove('show'); setTimeout(() => el.remove(), 150); };
    el.querySelector('.btn-close').addEventListener('click', close);
    setTimeout(close, delay);
  }
}
