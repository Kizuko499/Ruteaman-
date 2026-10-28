<?php
namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateAdminLoginLink extends Command
{
    protected $signature = 'admin:login-link {--minutes=15 : Masa berlaku link dalam menit}';
    protected $description = 'Buat magic login link sekali pakai untuk Admin Utama (Owner)';

    public function handle()
    {
        $owner = User::where('role', Role::Owner)->where('active', true)->first();

        if (!$owner) {
            $this->error('Tidak ada akun Owner yang aktif.');
            return 1;
        }

        $token = Str::random(64);
        $minutes = (int) $this->option('minutes');

        Cache::store('file')->put('admin-login:'.$token, $owner->id, now()->addMinutes($minutes));

        $url = url('/admin/magic-login/'.$token);

        $this->info('Magic login link (berlaku '.$minutes.' menit, sekali pakai):');
        $this->line($url);
        $this->warn('JANGAN bagikan link ini ke siapapun!');

        return 0;
    }
}
