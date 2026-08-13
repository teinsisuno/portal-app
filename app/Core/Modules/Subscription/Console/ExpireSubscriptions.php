<?php

namespace App\Core\Modules\Subscription\Console;

use App\Core\Modules\Subscription\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Lifecycle subscription: transisi yang jatuh tempo otomatis.
 *
 * - trialing + trial_ends_at lewat  -> past_due
 * - active  + ends_at lewat         -> past_due
 *
 * Dijalankan via scheduler (routes/console.php) — di production wajib ada
 * cron `php artisan schedule:run` tiap menit (lihat AGENTS.md deploy).
 */
class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Tandai subscription yang trial/aktif-nya lewat jatuh tempo menjadi past_due';

    public function handle(): int
    {
        $trialExpired = Subscription::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update(['status' => 'past_due']);

        $activeExpired = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => 'past_due']);

        $this->info("Trial expired: {$trialExpired}, active expired: {$activeExpired}");

        return self::SUCCESS;
    }
}
