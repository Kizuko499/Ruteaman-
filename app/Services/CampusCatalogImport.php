<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use RuntimeException;
final class CampusCatalogImport {
 public function run(string $file,string $metadata):int {
  if(!is_readable($file)||!is_readable($metadata))throw new RuntimeException('Berkas atau metadata katalog tidak ditemukan.');
  $meta=json_decode(file_get_contents($metadata),true,32,JSON_THROW_ON_ERROR);
  if(($meta['format']??null)!=='ruteaman-campus-catalog-v1'||!isset($meta['row_count'],$meta['sha256'])||!hash_equals($meta['sha256'],hash_file('sha256',$file)))throw new RuntimeException('Format metadata atau checksum katalog tidak sesuai.');
  $handle=gzopen($file,'rb');if(!$handle)throw new RuntimeException('Berkas katalog tidak dapat dibaca.');
  try{return DB::transaction(function()use($handle,$meta){$batch=[];$seen=[];$n=0;$stamp=now();
   while(!gzeof($handle)){$line=gzgets($handle,1048576);if($line===false)break;if(trim($line)==='')continue;$r=json_decode($line,true,32,JSON_THROW_ON_ERROR);$this->validate($r);
    if(isset($seen[$r['source_key']]))throw new RuntimeException('Duplikat source_key pada snapshot.');$seen[$r['source_key']]=true;
    $r['search_text']=mb_strtolower(implode(' ',[$r['name'],$r['official_code']??'',$r['city']??'',$r['region']??'',...$r['aliases']]));$r['aliases']=json_encode($r['aliases'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$r['school_id']=null;$r['created_at']=$stamp;$r['updated_at']=$stamp;$batch[]=$r;$n++;
    if(count($batch)===300){$this->save($batch);$batch=[];}
   }
   if($n!==(int)$meta['row_count'])throw new RuntimeException('Jumlah baris tidak cocok dengan metadata.');if($batch)$this->save($batch);return $n;
  });}finally{gzclose($handle);}
 }
 private function validate(array $r):void {
  $keys=['source_key','name','country_code','official_code','aliases','source','source_url','city','region','reference_latitude','reference_longitude','coordinate_accuracy','source_status','data_release'];
  if(array_diff($keys,array_keys($r))||array_diff(array_keys($r),$keys)||!in_array($r['source'],['pddikti','ror'],true)||!is_string($r['source_key'])||!preg_match('/^(pddikti:[A-Za-z0-9._-]{1,64}|ror:[a-z0-9]{9})$/',$r['source_key'])||!is_string($r['country_code'])||!preg_match('/^[A-Z]{2}$/',$r['country_code'])||!is_string($r['name'])||mb_strlen($r['name'])<2||mb_strlen($r['name'])>255||!is_array($r['aliases'])||count($r['aliases'])>100)throw new RuntimeException('Record katalog tidak valid.');
  foreach($r['aliases'] as $v)if(!is_string($v)||mb_strlen($v)>500)throw new RuntimeException('Alias tidak valid.');
  foreach(['official_code'=>64,'city'=>255,'region'=>255,'source_status'=>60,'data_release'=>100]as $k=>$len)if($r[$k]!==null&&(!is_string($r[$k])||mb_strlen($r[$k])>$len))throw new RuntimeException('Metadata record tidak valid.');
  if(!in_array($r['coordinate_accuracy'],['city_reference_only','unavailable'],true))throw new RuntimeException('Akurasi koordinat tidak valid.');
  foreach(['reference_latitude'=>90,'reference_longitude'=>180]as $k=>$bound)if($r[$k]!==null&&(!is_numeric($r[$k])||!is_finite((float)$r[$k])||abs((float)$r[$k])>$bound))throw new RuntimeException('Koordinat referensi tidak valid.');
  $government='https://data.kemdiktisaintek.go.id/dataset/data-jumlah-perguruan-tinggi-dan-status-akreditasi-pada-tahun-2025';
  if(!is_string($r['source_url'])||($r['source']==='pddikti'&&($r['source_url']!==$government||$r['country_code']!=='ID'||$r['official_code']!==substr($r['source_key'],8)||!str_starts_with($r['source_key'],'pddikti:')))||($r['source']==='ror'&&($r['source_url']!=='https://ror.org/'.substr($r['source_key'],4)||!str_starts_with($r['source_key'],'ror:'))))throw new RuntimeException('Asal sumber tidak valid.');
 }
 private function save(array $rows):void {DB::table('campus_catalog')->upsert($rows,['source_key'],['name','country_code','official_code','aliases','search_text','source','source_url','city','region','reference_latitude','reference_longitude','coordinate_accuracy','source_status','data_release','updated_at']);}
}
