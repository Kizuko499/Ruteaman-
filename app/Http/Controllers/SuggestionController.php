<?php

namespace App\Http\Controllers;

use App\Models\InstitutionSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    /**
     * Simpan saran/laporan dari pengguna publik.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(InstitutionSuggestion::categories()))],
            'institution_id' => ['nullable', 'integer', 'exists:schools,id'],
            'proposed_name' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'province' => ['nullable', 'string', 'max:100'],
            'regency' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $suggestion = InstitutionSuggestion::create($data);

        return response()->json([
            'data' => [
                'ticket_number' => $suggestion->ticket_number,
                'message' => 'Laporan berhasil dikirim dan menunggu peninjauan.',
            ]
        ], 201);
    }

    /**
     * Cek status usulan berdasarkan nomor tiket.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ticket_number' => ['required', 'string', 'max:20'],
        ]);

        $s = InstitutionSuggestion::where('ticket_number', strtoupper($data['ticket_number']))->first();

        if (!$s) {
            return response()->json(['message' => 'Nomor tiket tidak ditemukan.'], 404);
        }

        return response()->json(['data' => [
            'ticket_number' => $s->ticket_number,
            'proposed_name' => $s->proposed_name,
            'category' => InstitutionSuggestion::categories()[$s->category] ?? $s->category,
            'status' => InstitutionSuggestion::statuses()[$s->status] ?? $s->status,
            'review_notes' => $s->review_notes,
            'created_at' => $s->created_at->format('d M Y H:i'),
        ]]);
    }
}
