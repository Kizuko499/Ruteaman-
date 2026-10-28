<?php

namespace App\Http\Controllers;

use App\Models\InstitutionSuggestion;
use Illuminate\Http\Request;

class AdminSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = InstitutionSuggestion::with(['institution', 'reviewer'])->orderByDesc('created_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }
        if ($q = $request->get('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('ticket_number', 'like', '%' . $q . '%')
                  ->orWhere('proposed_name', 'like', '%' . $q . '%')
                  ->orWhere('npsn', 'like', '%' . $q . '%');
            });
        }

        $suggestions = $query->paginate(20);
        $newCount = InstitutionSuggestion::where('status', 'new')->count();

        return view('admin.suggestions', compact('suggestions', 'newCount'));
    }

    /**
     * Setujui usulan sekolah baru dan buatkan entri School.
     */
    public function approve(Request $request, InstitutionSuggestion $suggestion)
    {
        if ($suggestion->category !== 'new_school') {
            return back()->with('error', 'Hanya usulan sekolah baru yang bisa dibuatkan.');
        }

        $lat = $suggestion->latitude ?? -6.1754;
        $lng = $suggestion->longitude ?? 106.8272;
        if ($lat < -11.5 || $lat > 6.5 || $lng < 94 || $lng > 142.5) {
            return back()->with('error', 'Koordinat usulan tidak valid. Periksa di peta dulu.');
        }

        $delta = 0.02;
        $schoolId = \Illuminate\Support\Facades\DB::transaction(function () use ($suggestion, $lat, $lng, $delta, $request) {
            $id = \Illuminate\Support\Facades\DB::table('schools')->insertGetId([
                'name' => $suggestion->proposed_name,
                'npsn' => $suggestion->npsn,
                'institution_type' => 'school',
                'country_code' => 'ID',
                'status' => 'active',
                'public_slug' => \Illuminate\Support\Str::slug($suggestion->proposed_name) . '-' . substr(strtolower($suggestion->ticket_number), 4),
                'timezone' => 'Asia/Jakarta',
                'location' => \Illuminate\Support\Facades\DB::raw("ST_GeomFromText('POINT($lng $lat)')"),
                'catchment' => \Illuminate\Support\Facades\DB::raw("ST_GeomFromText('POLYGON((". ($lng-$delta)." ".($lat-$delta).", ".($lng+$delta)." ".($lat-$delta).", ".($lng+$delta)." ".($lat+$delta).", ".($lng-$delta)." ".($lat+$delta).", ".($lng-$delta)." ".($lat-$delta)."))')"),
                'features' => json_encode(['reporting' => true]),
                'settings' => json_encode([]),
            ]);

            $suggestion->update([
                'status' => 'approved',
                'institution_id' => $id,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => 'Otomatis dibuatkan dari usulan.',
            ]);

            \App\Models\InstitutionChangeLog::log($id, 'created', null,
                ['name' => $suggestion->proposed_name, 'npsn' => $suggestion->npsn],
                $request->user()->id, 'Usulan ' . $suggestion->ticket_number);

            return $id;
        });

        return back()->with('success', 'Sekolah dibuat (ID: '.$schoolId.') dan usulan disetujui.');
    }

    public function export()
    {
        $rows = InstitutionSuggestion::orderByDesc('id')->limit(2000)->get();
        $callback = function () use ($rows) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['ticket', 'category', 'name', 'npsn', 'province', 'regency', 'status', 'created_at']);
            foreach ($rows as $s) {
                fputcsv($h, [$s->ticket_number, $s->category, $s->proposed_name, $s->npsn, $s->province, $s->regency, $s->status, $s->created_at]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="usulan-' . date('Ymd') . '.csv"',
        ]);
    }

    public function show(InstitutionSuggestion $suggestion)
    {
        $suggestion->load(['institution', 'reviewer']);
        return view('admin.suggestion-detail', compact('suggestion'));
    }

    public function update(Request $request, InstitutionSuggestion $suggestion)
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(InstitutionSuggestion::statuses()))],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $suggestion->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Status laporan diperbarui.');
    }
}
