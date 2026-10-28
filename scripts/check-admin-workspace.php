<?php
// Read-only checks: no database connection and no .env/token output.
$base=dirname(__DIR__);$errors=[];
foreach(['app/Models/SitePresentation.php','app/Services/SitePresentationService.php','app/Http/Controllers/AdminPresentationController.php','app/Http/Controllers/AdminSchoolContactController.php','app/Http/Controllers/AdminGuideController.php','config/admin_guide.php','database/migrations/2026_10_15_000000_create_site_presentation.php','resources/views/admin/presentation.blade.php','resources/views/admin/school-contacts.blade.php','resources/views/admin/guide.blade.php','resources/views/pages/account/layout.blade.php'] as $f)if(!is_file($base.'/'.$f))$errors[]='File hilang: '.$f;
foreach(['imagecreatefromstring','imagewebp','imagescale'] as $f)if(!function_exists($f))$errors[]='PHP GD/WebP belum tersedia: '.$f;
$roots=[$base.'/public'];$host=dirname($base).'/public_html';if(is_dir($host))$roots[]=$host;
$guide=is_file($base.'/config/admin_guide.php')?require $base.'/config/admin_guide.php':['sections'=>[]];
foreach($roots as $root)foreach($guide['sections'] as $section)if($section['image']&&!is_file($root.'/images/admin-guide/'.$section['image']))$errors[]='Screenshot hilang: '.$root.'/images/admin-guide/'.$section['image'];
$manifest=json_decode(@file_get_contents($base.'/public/build/manifest.json'),true);if(!$manifest)$errors[]='Manifest build tidak valid.';
foreach($roots as $root)foreach($manifest??[] as $entry)foreach(array_merge([$entry['file']??''],$entry['css']??[],$entry['assets']??[]) as $f)if($f&&!is_file($root.'/build/'.$f))$errors[]='Aset hilang: '.$root.'/build/'.$f;
if($errors){foreach(array_unique($errors) as $e)fwrite(STDERR,$e."\n");exit(1);}echo "OK — file konsol, screenshot panduan, aset build, dan PHP GD/WebP lengkap.\nJalankan migration, lalu uji Tampilan & Kontak, Kontak Sekolah, Panduan Administrator, dan Akun saya.\n";
