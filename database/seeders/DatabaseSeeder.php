<?php
namespace Database\Seeders;

use App\Enums\Role;
use App\Models\{HazardType,School,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Hash};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
             $schools=[
            ['SMAN 8 Nusantara','31.71.01.1001',106.8272,-6.1754,'POLYGON((106.80 -6.20,106.86 -6.20,106.86 -6.15,106.80 -6.15,106.80 -6.20))'],
            ['SMKN 2 Nusantara','31.71.01.1002',106.8420,-6.1850,'POLYGON((106.815 -6.210,106.870 -6.210,106.870 -6.160,106.815 -6.160,106.815 -6.210))'],
            ['MAN 1 Nusantara','31.71.01.1003',106.8125,-6.1880,'POLYGON((106.785 -6.215,106.840 -6.215,106.840 -6.160,106.785 -6.160,106.785 -6.215))'],
        ];
        foreach($schools as [$name,$area,$lng,$lat,$polygon]){
            $point=\App\Services\SpatialSql::wkt(DB::getPdo()->quote("POINT($lng $lat)"));
            $polygonSql=\App\Services\SpatialSql::wkt();
            DB::statement("INSERT INTO schools (name,bmkg_area_code,timezone,location,catchment) VALUES (?, ?, ?, {$point}, {$polygonSql})",[$name,$area,'Asia/Jakarta',$polygon]);
        }
        $school=School::query()->first();
        $types=[
            ['flood','Genangan / banjir',3,180,true,false],['fallen_tree','Pohon tumbang',3,720,false,false],
            ['obstruction','Hambatan di jalan',2,720,false,false],['slippery','Jalan licin',1,360,true,false],
            ['pothole','Jalan berlubang',2,43200,false,false],['damaged_road','Jalan rusak',2,43200,false,false],
            ['damaged_sidewalk','Trotoar rusak',2,43200,false,false],['poor_lighting','Jalan gelap',2,20160,false,true],
        ];
        foreach($types as [$code,$label,$weight,$ttl,$rain,$night]) HazardType::query()->create(['code'=>$code,'label'=>$label,'severity_weight'=>$weight,'ttl_minutes'=>$ttl,'rain_sensitive'=>$rain,'night_only'=>$night]);
        foreach([
            ['Siswa Demo','siswa@ruteaman.test',Role::Student],
            ['Verifikator Demo','verifikator@ruteaman.test',Role::SchoolAdmin],
            ['Admin Sekolah','admin@ruteaman.test',Role::SchoolAdmin],
        ] as [$name,$email,$role]) User::query()->create(['school_id'=>$school->id,'display_name'=>$name,'email'=>$email,'password'=>Hash::make('RuteAman123!'),'role'=>$role,'permissions'=>config('ruteaman_roles.ceilings.'.($role instanceof \App\Enums\Role?$role->value:$role),[]),'status'=>'active','parental_consent_at'=>now(),'active'=>true]);
    }
}