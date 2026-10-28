<?php
$root=dirname(__DIR__);
$directories=[
    $root.'/storage/framework/views',
    $root.'/storage/framework/cache/data',
    $root.'/storage/framework/sessions',
    $root.'/storage/logs',
    $root.'/bootstrap/cache',
];
foreach($directories as $directory){
    if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory)){
        fwrite(STDERR,"Tidak dapat membuat direktori: {$directory}\n");
        exit(1);
    }
}
echo "Direktori runtime Laravel siap.\n";
