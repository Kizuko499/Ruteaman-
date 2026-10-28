<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\HazardComment;
use App\Policies\HazardPublicPolicy;
use Illuminate\Http\Request;

final class HazardCommentController extends Controller
{
    public function store(Request $request, Hazard $hazard)
    {
        // Komentar hanya untuk hazard yang boleh dilihat publik.
        abort_unless(app(HazardPublicPolicy::class)->view($request->user(), $hazard), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $comment = $hazard->comments()->create([
            'user_id' => $request->user()?->id,
            'body' => trim($data['body']),
        ]);

        app(\App\Services\ReportMailNotifier::class)->commentAdded($comment);

        return back()->with('success', __('Komentar terkirim.'));
    }

    public function destroy(Request $request, Hazard $hazard, HazardComment $comment)
    {
        // Komentar harus milik hazard pada URL ini.
        abort_unless($comment->hazard_id === $hazard->id, 404);

        $user = $request->user();
        abort_unless($user && app(\App\Services\PermissionService::class)->can($user, 'comments.delete'), 403);

        $excerpt = \Illuminate\Support\Str::limit($comment->body, 80);
        $comment->delete();

        app(\App\Services\AdminAuditService::class)->write($user, 'comment.deleted', 'hazard_comment', $comment->id, ['hazard' => $hazard->public_slug, 'excerpt' => $excerpt]);

        return back()->with('success', __('Komentar dihapus.'));
    }
}
