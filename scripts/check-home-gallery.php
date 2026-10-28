<?php
$base=dirname(__DIR__);$errors=[];
$required=['app/Services/HomeGalleryService.php','app/Models/HomeGalleryGroup.php','app/Models/HomeGalleryPhoto.php','app/Http/Controllers/HomeGalleryController.php','app/Http/Controllers/AdminHomeGalleryController.php','config/home_gallery.php','resources/views/admin/home-gallery.blade.php','resources/views/partials/home-gallery-slots.blade.php','database/migrations/2026_10_13_000000_create_home_gallery.php','public/build/manifest.json'];
foreach($required as $path)if(!is_file($base.'/'.$path))$errors[]='File hilang: '.$path;
foreach(['imagecreatefromstring','imagewebp','imagescale'] as $function)if(!function_exists($function))$errors[]='PHP GD/WebP tidak tersedia: '.$function;
$manifest=json_decode(@file_get_contents($base.'/public/build/manifest.json'),true);if(!$manifest)$errors[]='Manifest build tidak valid.';
$roots=[$base.'/public'];$host=dirname($base).'/public_html';if(is_dir($host))$roots[]=$host;
foreach($roots as $root){foreach($manifest??[] as $entry)foreach(array_merge([$entry['file']??''], $entry['css']??[], $entry['assets']??[]) as $file)if($file&&!is_file($root.'/build/'.$file))$errors[]='Aset hilang: '.$root.'/build/'.$file;
foreach(['siswa-berangkat-ke-sekolah.webp','banjir-jalan.webp','jalan-berlubang.webp','pohon-tumbang.webp','minim-penerangan.webp'] as $image)if(!is_file($root.'/images/'.$image))$errors[]='Ilustrasi hilang: '.$root.'/images/'.$image;}
if($errors){foreach(array_unique($errors) as $error)fwrite(STDERR,$error."\n");exit(1);}echo "OK — file galeri, aset build, ilustrasi, dan PHP GD/WebP tersedia.\nJalankan php artisan migrate --force untuk membuat tabel, lalu uji menu Foto Beranda dengan Administrator Utama.\n";
