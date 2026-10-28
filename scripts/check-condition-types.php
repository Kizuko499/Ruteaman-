<?php
// Read-only file/dependency check; no database access and no credential output.
$base=dirname(__DIR__);$errors=[];
foreach(['config/condition_icons.php','app/Services/ConditionIconService.php','app/Http/Controllers/AdminConditionTypeController.php','app/Models/HazardType.php','database/migrations/2026_10_14_000000_add_condition_type_icons.php','resources/views/admin/condition-types.blade.php','resources/views/admin/partials/condition-type-fields.blade.php','resources/js/features/map/condition-types.js','resources/views/partials/mobile-nav.blade.php'] as $file)if(!is_file($base.'/'.$file))$errors[]='File hilang: '.$file;
foreach(['imagecreatefromstring','imagewebp','imagecopyresampled'] as $func)if(!function_exists($func))$errors[]='PHP GD/WebP tidak tersedia: '.$func;
$manifest=json_decode(@file_get_contents($base.'/public/build/manifest.json'),true);if(!$manifest)$errors[]='Manifest build tidak ditemukan / tidak valid.';
$roots=[$base.'/public'];if(is_dir(dirname($base).'/public_html'))$roots[]=dirname($base).'/public_html';
foreach($roots as $root)foreach($manifest??[] as $entry)foreach(array_merge([$entry['file']??''],$entry['css']??[],$entry['assets']??[]) as $file)if($file&&!is_file($root.'/build/'.$file))$errors[]='Aset hilang: '.$root.'/build/'.$file;
if($errors){foreach(array_unique($errors) as $e)fwrite(STDERR,$e."\n");exit(1);}echo "OK — file jenis kondisi, aset build, dan PHP GD/WebP tersedia.\nMigration harus dijalankan, lalu buka Administrator Utama > Jenis Kondisi.\n";
