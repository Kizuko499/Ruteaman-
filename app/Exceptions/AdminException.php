<?php

namespace App\Exceptions;

use App\Services\AdminAuditService;
use Illuminate\Http\Request;

class AdminException extends \RuntimeException
{
    public function __construct(public string $errorCode, string $message, public int $status = 403)
    {
        parent::__construct($message);
    }

    public function render(Request $r)
    {
        if (in_array($this->errorCode, ['FORBIDDEN_SCOPE', 'GRANT_CEILING_EXCEEDED'], true) && $r->user()) {
            if (! $r->attributes->get('audit_denial_recorded')) {
                app(AdminAuditService::class)->write($r->user(), 'permission.denied', 'user', $r->user()->id, [], $r->user()->school_id, [], null, 'denied');
                $r->attributes->set('audit_denial_recorded', true);
            }$key = 'admin.denied.'.$r->user()->id;
            $n = cache()->get($key, 0) + 1;
            cache()->put($key, $n, now()->addMinutes(10));
            if ($n === 5) {
                app(AdminAuditService::class)->notifyOwners('Percobaan akses melewati batas berulang oleh akun #'.$r->user()->id);
            }
        }if ($r->expectsJson()) {
            return response()->json(['error' => ['code' => $this->errorCode, 'message' => $this->getMessage(), 'field_errors' => []], 'message' => $this->getMessage()], $this->status);
        }

        return response()->view('admin.error', ['code' => $this->errorCode, 'message' => $this->getMessage()], $this->status);
    }
}
