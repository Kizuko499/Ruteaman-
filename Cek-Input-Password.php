<?php
// Diagnostics only. Never creates an account or writes a password/file/log.
$autoload=__DIR__.'/vendor/autoload.php';
if(!is_file($autoload)){fwrite(STDERR,"Letakkan file ini di folder proyek, sejajar dengan artisan. Jalankan composer install jika vendor belum ada.\n");exit(1);}
require $autoload;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\Question;
$out=new ConsoleOutput();
$out->writeln('Pemeriksaan input tersembunyi — TIDAK membuat akun.');
$out->writeln('Ketik ABCD sebagai input uji, BUKAN password asli, lalu tekan Enter.');
$out->writeln('Sistem: '.PHP_OS_FAMILY);
if(PHP_OS_FAMILY==='Windows'){
 $r=new ReflectionClass(QuestionHelper::class);
 $helper=dirname($r->getFileName()).'/../Resources/bin/hiddeninput.exe';
 $out->writeln('Helper Windows tersedia: '.(is_file($helper)?'YA':'TIDAK'));
 $out->writeln('shell_exec tersedia: '.(function_exists('shell_exec')?'YA':'TIDAK'));
}
try{
 $question=(new Question('Input uji ABCD: '))->setHidden(true)->setHiddenFallback(false);
 $answer=(new QuestionHelper())->ask(new ArgvInput(),$out,$question);
 $length=strlen((string)$answer);unset($answer);
 $out->writeln('Jumlah karakter terbaca: '.$length);
 $out->writeln($length===4?'Input diterima. Tidak munculnya teks adalah penyembunyian normal.':($length===0?'Input kosong. Coba ulang pada Command Prompt (CMD) biasa.':'Input diterima, tetapi jumlahnya bukan 4. Ulangi dengan ABCD untuk memastikan.'));
}catch(Throwable $e){$out->writeln('Pembaca input tersembunyi gagal. Tidak ada password yang disimpan.');$out->writeln('Coba jalankan dari Command Prompt (CMD) biasa, lalu periksa pemasangan vendor dan izin helper Windows.');exit(1);}
