<?php

namespace App\Console\Commands;

use App\Services\DigestDispatcher;
use Illuminate\Console\Command;

class DispatchNotificationDigests extends Command
{
    protected $signature = 'notifications:dispatch-digests';

    protected $description = 'Kirim ringkasan email berkala untuk penerima notifikasi yang jatuh tempo';

    public function handle(DigestDispatcher $dispatcher): int
    {
        $stats = $dispatcher->run();
        $this->info("Ringkasan: {$stats['sent']} dikirim, {$stats['skipped_empty']} dilewati (kosong), {$stats['postponed']} ditunda (jam tenang), {$stats['already']} sudah terproses.");

        return self::SUCCESS;
    }
}
