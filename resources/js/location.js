const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
// One-shot location only: never request on page load and never use watchPosition.
export function bindLocationButton(button, notice, onPosition) {
 if (!button || !notice) return;
 const message = notice.querySelector('[data-location-message]');
 const guide = notice.querySelector('[data-location-guide]');
 const label = button.textContent;
 function show(state, text, expand = false) {
  notice.dataset.state = state;
  message.textContent = text;
  if (expand) guide.open = true;
 }
 const insecure = uiText("GPS browser memerlukan HTTPS. Alamat HTTP dari IP laptop tidak mendukung lokasi perangkat. Pilih titik di peta, atau buka situs melalui HTTPS yang dipercaya Chrome.");
 if (!window.isSecureContext) show('error', insecure);
 button.addEventListener('click', () => {
  if (button.disabled) return;
  if (!window.isSecureContext) { show('error', insecure, true); return; }
  if (!navigator.geolocation) { show('error', uiText("Browser ini tidak menyediakan akses lokasi. Pilih titik langsung di peta."), true); return; }
  button.disabled = true;
  button.textContent = uiText("Meminta lokasi…");
  show('pending', uiText("Menunggu lokasi. Pastikan Lokasi / GPS aktif dan pilih Izinkan pada permintaan Chrome."));
  const reset = () => { button.disabled = false; button.textContent = label; };
  const failure = error => {
   reset();
   const text = error.code === 1
    ? uiText("Izin lokasi ditolak atau diblokir. Periksa izin lokasi Chrome dan situs ini, lalu coba lagi. Kamu tetap dapat memilih titik di peta.")
    : error.code === 3
     ? uiText("Pencarian lokasi melewati batas waktu. Pastikan Lokasi / GPS aktif, coba di tempat dengan sinyal lebih baik, lalu tekan tombol lagi. Atau pilih titik di peta.")
     : uiText("Lokasi belum bisa dibaca. Periksa Lokasi / GPS dan koneksi perangkat. Ini tidak selalu berarti GPS mati. Coba lagi atau pilih titik di peta.");
   show('error', text, true);
  };
  try {
   navigator.geolocation.getCurrentPosition(position => {
    reset();
    const accuracy = position.coords.accuracy;
    show('success', accuracy > 50
     ? uiText("Lokasi terbaca, tetapi akurasi sekitar :v0: m. Periksa titik dan pilih ulang di peta bila kurang tepat.", [Math.round(accuracy)])
     : uiText("Lokasi berhasil dibaca satu kali. Periksa titik di peta sebelum melanjutkan."));
    onPosition(position);
   }, failure, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
  } catch { failure({ code: 2 }); }
 });
}
