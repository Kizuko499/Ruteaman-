<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Verifikasi Penerima Notifikasi — RuteAman</title></head>
<body style="margin:0;padding:24px;background:#f4f7f7;font-family:Arial,Helvetica,sans-serif;color:#173a3c;">
<div style="max-width:520px;margin:40px auto;background:#ffffff;border-radius:14px;padding:28px;border:1px solid #dcebe9;">
<p style="margin:0 0 16px;font-size:20px;font-weight:bold;color:#1F6E70;">RuteAman</p>
@if($state === 'ok')
<p style="font-size:17px;font-weight:bold;">Alamat email terverifikasi.</p>
<p>Terima kasih, {{ $recipient->name }}. Alamat <strong>{{ $recipient->email }}</strong> sekarang aktif sebagai penerima notifikasi RuteAman sesuai langganan yang diatur administrator. Kamu dapat menutup halaman ini.</p>
@elseif($state === 'expired')
<p style="font-size:17px;font-weight:bold;">Tautan sudah kedaluwarsa.</p>
<p>Tautan verifikasi ini berlaku 72 jam dan sudah lewat. Minta administrator RuteAman mengirim ulang email verifikasi dari halaman Penerima Notifikasi.</p>
@else
<p style="font-size:17px;font-weight:bold;">Tautan tidak valid.</p>
<p>Tautan verifikasi tidak cocok atau sudah pernah dipakai. Minta administrator RuteAman mengirim ulang email verifikasi bila kamu memang didaftarkan sebagai penerima notifikasi.</p>
@endif
</div>
</body>
</html>
