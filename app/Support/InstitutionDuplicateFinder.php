<?php

namespace App\Support;

/**
 * Mengelompokkan institusi yang terindikasi duplikat dari baris ringkas
 * (id, nama, npsn, koordinat). Dua aturan kuat saja agar hasilnya dapat
 * ditinjau manual tanpa false positive berlebihan:
 *  1. NPSN yang sama persis.
 *  2. Nama ternormalisasi yang sama + koordinat sama (dibulatkan 4 desimal).
 * Kelompok nama+koordinat yang anggotanya persis sama dengan kelompok NPSN
 * tidak diulang. Murni data — tanpa akses basis data.
 */
final class InstitutionDuplicateFinder
{
    public static function normalizeName(string $name): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($name))) ?? '';
    }

    /**
     * @param  list<array{id: int, name: string, npsn: ?string, lat: ?float, lng: ?float}>  $rows
     * @return list<array{kind: string, label: string, member_ids: list<int>}>
     */
    public static function groups(array $rows): array
    {
        $byNpsn = [];
        $byNameCoords = [];
        foreach ($rows as $row) {
            $npsn = trim((string) ($row['npsn'] ?? ''));
            if ($npsn !== '') {
                $byNpsn[$npsn][] = (int) $row['id'];
            }
            if (($row['lat'] ?? null) !== null && ($row['lng'] ?? null) !== null) {
                $key = self::normalizeName((string) $row['name']).'|'.round((float) $row['lat'], 4).'|'.round((float) $row['lng'], 4);
                $byNameCoords[$key][] = (int) $row['id'];
            }
        }

        $groups = [];
        $seenSets = [];
        foreach ($byNpsn as $npsn => $ids) {
            $ids = self::uniqueSorted($ids);
            if (count($ids) > 1) {
                $seenSets[implode(',', $ids)] = true;
                $groups[] = ['kind' => 'npsn', 'label' => 'NPSN '.$npsn, 'member_ids' => $ids];
            }
        }
        foreach ($byNameCoords as $key => $ids) {
            $ids = self::uniqueSorted($ids);
            if (count($ids) <= 1 || isset($seenSets[implode(',', $ids)])) {
                continue;
            }
            [$name, $lat, $lng] = explode('|', $key);
            $groups[] = ['kind' => 'name_coords', 'label' => $name.' @ '.$lat.', '.$lng, 'member_ids' => $ids];
        }

        return $groups;
    }

    /** @return list<int> */
    private static function uniqueSorted(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
