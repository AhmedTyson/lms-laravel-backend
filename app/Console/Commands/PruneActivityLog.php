<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

// ADR-014: nightly prune of the audit trail. Window comes from
// ACTIVITY_LOG_RETENTION_DAYS (default 90, decided — not silent).
// permission_grants is NEVER pruned by this command: it is the permanent
// delegation ledger (RULE-005), while activity_log is the supplementary trail.
class PruneActivityLog extends Command
{
    protected $signature = 'activitylog:prune';

    protected $description = 'Delete activity_log rows older than the retention window';

    public function handle(): int
    {
        $days = (int) config('audit.retention_days', 90);
        $cutoff = now()->subDays($days);

        $deleted = Activity::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} activity_log rows older than {$days} days.");

        return self::SUCCESS;
    }
}
