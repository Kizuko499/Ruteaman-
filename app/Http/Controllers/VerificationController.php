<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\Verification;
use App\Services\HazardEvidenceService;
use App\Services\VerificationInboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(): View
    {
        return view('verifier.queue', app(VerificationInboxService::class)->records(request()));
    }

    public function store(Request $request, Hazard $hazard, HazardEvidenceService $evidence): RedirectResponse
    {
        app(VerificationInboxService::class)->authorize($request->user(), $hazard);
        abort_if($hazard->reports()->where('reporter_id', $request->user()->id)->exists(), 403, 'Tidak dapat memverifikasi laporan sendiri.');
        $d = $request->validate(['decision' => 'required|in:verified,rejected,needs_evidence', 'note' => 'nullable|string|max:1000', 'location_label' => 'sometimes|nullable|string|max:120|not_regex:/[<>]/']);
        DB::transaction(function () use ($request, $hazard, $d, $evidence) {
            $h = Hazard::whereKey($hazard->id)->lockForUpdate()->firstOrFail();
            $beforeStatus = $h->status->value;
            app(VerificationInboxService::class)->authorize($request->user(), $h);
            abort_unless(in_array($h->status->value, ['new', 'confirmed', 'disputed', 'expired', 'verified'], true), 409, 'Laporan sudah ditutup.');
            if (array_key_exists('location_label', $d)) {
                $h->update(['location_label' => trim($d['location_label'] ?? '') ?: null]);
            }
            $previous = $h->verifications()->latest('id')->first();
            if ($previous && $previous->decision === $d['decision'] && $previous->verifier_id === $request->user()->id && ($previous->note ?? '') === ($d['note'] ?? '')) {
                return;
            }
            Verification::create(['hazard_id' => $h->id, 'verifier_id' => $request->user()->id, 'decision' => $d['decision'], 'note' => $d['note'] ?? null, 'created_at' => now()]);
            if ($h->requires_moderation && $d['decision'] === 'verified') {
                $h->forceFill(['requires_moderation' => false, 'resolved_at' => null])->save();
            }
            $h->update(['status' => match ($d['decision']) {
                'verified' => 'verified','rejected' => 'resolved',default => 'disputed'
            }, 'resolved_at' => $d['decision'] === 'rejected' ? now() : null]);
            $evidence->refresh($h);
            $evidence->audit($request->user()->id, 'verification.'.$d['decision'], $h->id, [], ['status' => ['before' => $beforeStatus, 'after' => $h->status->value]], $d['note'] ?? null);
        });

        return back()->with('success', 'Verifikasi disimpan dan tercatat di audit log.');
    }
}
