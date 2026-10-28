<div class="location-help" data-location-help data-state="idle">
 <p class="location-help-title"><span aria-hidden="true">◎</span> {{ __('Siapkan Lokasi / GPS') }}</p>
 <p data-location-message role="status" aria-live="polite">{{ __('Jika ingin memakai lokasi perangkat, nyalakan Lokasi / GPS di HP dan izinkan akses lokasi di Chrome. Atau pilih titik langsung di peta.') }}</p>
 <details data-location-guide><summary>{{ __('Cara mengaktifkan di Android & Chrome') }}</summary>
  <ol>
   <li>{{ __('Tarik panel setelan cepat dari atas layar, lalu aktifkan') }} <strong>{{ __('Lokasi') }}</strong>{{ __('. Jika tidak terlihat, buka') }} <strong>{{ __('Setelan HP → Lokasi → Gunakan lokasi') }}</strong>.</li>
   <li>{{ __('Buka') }} <strong>{{ __('Setelan HP → Aplikasi → Chrome → Izin → Lokasi') }}</strong>{{ __(', lalu pilih') }} <strong>{{ __('Izinkan hanya saat aplikasi digunakan') }}</strong>{{ __('. Aktifkan lokasi akurat jika tersedia.') }}</li>
   <li>{{ __('Di Chrome, buka') }} <strong>{{ __('Menu tiga titik → Setelan → Setelan situs → Lokasi') }}</strong> {{ __('dan izinkan situs meminta lokasi. Jika situs ini diblokir, ubah izinnya dari daftar situs atau ikon informasi di sebelah alamat.') }}</li>
   <li>{{ __('Kembali ke RuteAman, tekan') }} <strong>{{ __('Gunakan lokasi sekali') }}</strong>{{ __(', lalu pilih') }} <strong>{{ __('Izinkan') }}</strong> {{ __('saat Chrome meminta izin.') }}</li>
  </ol>
  <p>{{ __('Nama menu dapat berbeda menurut merek HP dan versi Android. Web tidak bisa menyalakan GPS sendiri atau memastikan GPS sedang mati.') }}</p>
  <p><strong>{{ __('Untuk uji lewat Wi-Fi:') }}</strong> alamat seperti http://192.168.x.x:8000 biasanya tidak dapat memakai GPS browser. Gunakan HTTPS yang dipercaya browser, atau ketuk peta untuk memilih lokasi tanpa GPS.</p>
  <p>{{ __('Lokasi hanya diminta setelah tombol ditekan; tidak ada pelacakan terus-menerus.') }}</p>
 </details>
</div>
