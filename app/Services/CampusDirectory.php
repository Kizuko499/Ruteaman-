<?php
namespace App\Services;
use Illuminate\Support\Facades\{Cache,Http};
final class CampusDirectory {
 use CampusDirectoryLocalSearch;
 public function search(string $query,?string $country='ID',int $page=1):array {
  $country=$country?strtoupper($country):null;
  return Cache::remember('campus-ror-v2:'.hash('sha256',json_encode([$query,$country,$page])),3600,function()use($query,$country,$page){
   $filter='types:education,status:active'.($country?',locations.geonames_details.country_code:'.$country:'');
   $data=Http::acceptJson()->connectTimeout(4)->timeout(12)->get('https://api.ror.org/v2/organizations',['query'=>$query,'filter'=>$filter,'page'=>$page])->throw()->json();
   if(!is_array($data)||!is_array($data['items']??null))throw new \RuntimeException('Respons direktori tidak valid.');
   $items=[];foreach($data['items']as $row){if(($row['status']??'active')!=='active'||!in_array('education',$row['types']??[],true))continue;
    $name=null;foreach($row['names']??[]as $n){if(in_array('ror_display',$n['types']??[],true))$name=$n['value'];if(($n['lang']??null)==='id'&&in_array('label',$n['types']??[],true)){$name=$n['value'];break;}}
    $l=$row['locations'][0]['geonames_details']??[];$lat=$l['lat']??null;$lng=$l['lng']??null;
    if(!$name||!preg_match('~^https://ror\.org/[a-z0-9]{9}$~',(string)($row['id']??'')))continue;
    $valid=is_numeric($lat)&&is_numeric($lng)&&abs((float)$lat)<=90&&abs((float)$lng)<=180;
    $items[]=['id'=>$row['id'],'name'=>$name,'city'=>$l['name']??null,'country_code'=>$l['country_code']??null,'country_name'=>$l['country_name']??null,'latitude'=>$valid?(float)$lat:null,'longitude'=>$valid?(float)$lng:null,'coordinate_accuracy'=>'city_reference_only','requires_destination_pin'=>true];
   }
   $total=(int)($data['number_of_results']??count($items));return ['data'=>$items,'page'=>$page,'has_more'=>$page*20<min(10000,$total),'matched_records'=>$total,'source'=>'ROR v2 / GeoNames','coverage'=>'Registry coverage only; not a complete campus directory. Coordinates refer to cities, not campus gates.'];
  });
 }
}

/**
 * Lapisan pencarian direktori LOKAL (ditambahkan terpisah dari klien ROR di atas):
 * membaca katalog kampus yang dibundel di resources/data/campus-catalog-v3.jsonl.gz
 * (gabungan snapshot pemerintah/PDDikti 2025 + ROR v2.14) tanpa panggilan jaringan.
 */
trait CampusDirectoryLocalSearch
{
    public function searchLocal(string $query, int $limit = 14): array
    {
        $norm = $this->catalogNormalize($query);
        if ($norm === '') {
            return [];
        }
        $tokens = array_values(array_filter(explode(' ', $norm), fn ($t) => mb_strlen($t) >= 2));
        if ($tokens === []) {
            return [];
        }

        $phrase = [];
        $tokenHits = [];
        $stemHits = [];
        foreach ($this->catalogIndex() as $row) {
            $hay = $row['hay'];
            if (str_contains($hay, $norm)) {
                $phrase[] = $row;
                continue;
            }
            $ok = true;
            foreach ($tokens as $t) {
                if (!str_contains($hay, $t)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $tokenHits[] = $row;
                continue;
            }
            $ok = true;
            foreach ($tokens as $t) {
                $stem = mb_strlen($t) > 6 ? mb_substr($t, 0, 6) : $t;
                if (!str_contains($hay, $stem)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $stemHits[] = $row;
            }
        }

        $rank = function (array $rows): array {
            usort($rows, fn ($a, $b) => [$b['id_country'], $b['has_geo'], $a['name']] <=> [$a['id_country'], $a['has_geo'], $b['name']]);
            return $rows;
        };

        $out = [];
        foreach (array_merge($rank($phrase), $rank($tokenHits), $rank($stemHits)) as $row) {
            $out[] = [
                'id' => $row['id'],
                'campus_code' => $row['campus_code'],
                'name' => $row['name'],
                'city' => $row['city'],
                'country_code' => $row['country_code'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
            ];
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    private function catalogIndex(): array
    {
        $path = resource_path('data/campus-catalog-v3.jsonl.gz');
        if (!is_file($path)) {
            return [];
        }
        $key = 'campus-catalog-v3-index:' . filemtime($path) . ':' . filesize($path);
        return Cache::remember($key, 86400, function () use ($path) {
            $rows = [];
            $seen = [];
            $fh = gzopen($path, 'rb');
            if ($fh === false) {
                return [];
            }
            while (!gzeof($fh)) {
                $line = gzgets($fh);
                if ($line === false) {
                    break;
                }
                $row = json_decode(trim($line), true);
                if (!is_array($row) || empty($row['name'])) {
                    continue;
                }
                $isRor = ($row['source'] ?? '') === 'ror';
                $lat = $row['reference_latitude'] ?? null;
                $lng = $row['reference_longitude'] ?? null;
                $hasGeo = is_numeric($lat) && is_numeric($lng);
                $entry = [
                    'id' => $isRor ? ($row['source_url'] ?? null) : ($row['source_key'] ?? null),
                    'campus_code' => $isRor ? 'ROR:' . basename((string) ($row['source_url'] ?? '')) : 'PDDIKTI:' . ($row['official_code'] ?? ''),
                    'name' => $row['name'],
                    'city' => $row['city'] ?? null,
                    'country_code' => $row['country_code'] ?? 'ID',
                    'latitude' => $hasGeo ? (float) $lat : null,
                    'longitude' => $hasGeo ? (float) $lng : null,
                    'hay' => $this->catalogNormalize($row['name']) . ' ' . $this->catalogNormalize(implode(' ', $row['aliases'] ?? [])) . ' ' . $this->catalogNormalize((string) ($row['city'] ?? '')),
                    'id_country' => (($row['country_code'] ?? 'ID') === 'ID') ? 1 : 0,
                    'has_geo' => $hasGeo ? 1 : 0,
                ];
                if ($entry['id'] === null) {
                    continue;
                }
                $nameKey = $this->catalogNormalize($row['name']);
                if (isset($seen[$nameKey])) {
                    $i = $seen[$nameKey];
                    if (!$rows[$i]['has_geo'] && $hasGeo) {
                        $rows[$i] = $entry;
                    }
                    continue;
                }
                $seen[$nameKey] = count($rows);
                $rows[] = $entry;
            }
            gzclose($fh);
            return $rows;
        });
    }

    private function catalogNormalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? '';
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
