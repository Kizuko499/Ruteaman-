<?php
// Read-only file check. Does not load .env, print secrets, or touch a database.
$base=dirname(__DIR__);$errors=[];
$required=['app/Http/Controllers/UserGuideController.php','config/user_guide.php','resources/views/pages/account/guide.blade.php','resources/views/pages/account/profile.blade.php','resources/views/components/account-menu.blade.php','routes/web.php','resources/views/landing.blade.php','public/css/user-guide.css','public/css/home-guide.css'];
foreach($required as $path)if(!is_file($base.'/'.$path))$errors[]=$path;
if(is_file($base.'/config/user_guide.php')){
 try{$data=require $base.'/config/user_guide.php';foreach($data['sections'] as $s)foreach([$s['image'],...($s['extra_images']??[])] as $image){if(!is_file($base.'/public/images/user-guide/'.$image))$errors[]='public/images/user-guide/'.$image;}}
 catch(Throwable $e){$errors[]='config/user_guide.php tidak dapat dibaca; periksa kelengkapan file.';}
}
$webroot=dirname($base).'/public_html';
if(is_dir($webroot)){
 if(!is_file($webroot.'/css/home-guide.css'))$errors[]='public_html/css/home-guide.css';
 if(!is_file($webroot.'/css/user-guide.css'))$errors[]='public_html/css/user-guide.css';
 if(isset($data))foreach($data['sections'] as $s)foreach([$s['image'],...($s['extra_images']??[])] as $image)if(!is_file($webroot.'/images/user-guide/'.$image))$errors[]='public_html/images/user-guide/'.$image;
}
if(is_file($base.'/routes/web.php')&&!str_contains(file_get_contents($base.'/routes/web.php'),"name('account.guide')"))$errors[]='Route account.guide belum terpasang di routes/web.php';
if(is_file($base.'/routes/web.php')&&!str_contains(file_get_contents($base.'/routes/web.php'),"name('guide.public')"))$errors[]='Route guide.public belum terpasang';
if($errors){echo "BELUM LENGKAP:\n";foreach(array_unique($errors) as $e)echo ' - '.$e."\n";exit(1);}
echo 'OK: route, halaman, controller, CSS, dan '.(isset($data)?count($data['sections']):0)." bab beserta gambarnya tersedia.\n";
echo "Tanpa login: scroll bagian bawah beranda > Buka panduan pengguna, atau buka /panduan.\n";
echo "Login lalu buka Akun > Panduan Pengguna atau Profil Saya > Buka panduan pengguna.\n";
