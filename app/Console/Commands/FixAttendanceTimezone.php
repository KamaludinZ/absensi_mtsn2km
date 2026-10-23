<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repairs attendance times that were saved while the database connection
 * used the server's time zone (e.g. UTC on Coolify) instead of WIB.
 *
 * Such a time was saved as the WIB clock reading but marked as UTC, so it now
 * shows 7 hours late. It is recognised by comparing it with when the row was
 * written (created_at for clock-in, updated_at for clock-out, saved as plain
 * WIB clock readings): a correct time is within minutes of it, a shifted one
 * is exactly the time-zone difference later.
 */
class FixAttendanceTimezone extends Command
{
    protected $signature = 'app:fix-attendance-timezone
        {--from= : Time zone the wrong times were saved in (default: the database server default, e.g. Etc/UTC)}
        {--apply : Save the corrections (without it, only shows what would change)}';

    protected $description = 'Fix clock-in/clock-out times shifted by a wrong database time zone';

    /** How close to "exactly the time-zone difference" a shift must be */
    private const TOLERANCE_MINUTES = 15;

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->line('Only needed for PostgreSQL, nothing to do.');
            return self::SUCCESS;
        }

        $appZone = config('app.timezone');
        $from = $this->option('from')
            ?: DB::selectOne("select reset_val from pg_settings where name = 'TimeZone'")->reset_val;

        $shift = $this->offsetSeconds($appZone) - $this->offsetSeconds($from);
        if ($shift === 0) {
            $this->info("The database time zone ({$from}) matches {$appZone}: nothing to fix.");
            return self::SUCCESS;
        }

        $hours = $shift / 3600;
        $this->line("Looking for times saved as {$from} instead of {$appZone} (shown {$hours} hours off)...");

        $fixes = 0;
        foreach (['clock_in' => 'created_at', 'clock_out' => 'updated_at'] as $column => $writtenAt) {
            // Local (WIB) clock reading of the stored moment vs. when the row was written
            $rows = DB::table('attendances')
                ->selectRaw("id, date, {$column} as value, {$writtenAt} as written_at")
                ->whereNotNull($column)
                ->whereNotNull($writtenAt)
                ->whereRaw(
                    "abs(extract(epoch from ({$column} at time zone ?) - {$writtenAt}) - ?) <= ?",
                    [$appZone, $shift, self::TOLERANCE_MINUTES * 60]
                )
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $wrong = CarbonImmutable::parse($row->value)->setTimezone($appZone);
                $right = $wrong->subSeconds($shift);
                $this->line(sprintf(
                    '  #%d %s %s: %s -> %s',
                    $row->id,
                    substr((string) $row->date, 0, 10),
                    $column,
                    $wrong->format('Y-m-d H:i'),
                    $right->format('Y-m-d H:i')
                ));
            }

            if ($this->option('apply') && $rows->isNotEmpty()) {
                DB::table('attendances')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update([$column => DB::raw("{$column} - make_interval(secs => {$shift})")]);
            }

            $fixes += $rows->count();
        }

        if ($fixes === 0) {
            $this->info('No shifted times found.');
        } elseif ($this->option('apply')) {
            $this->info("Fixed {$fixes} time(s).");
        } else {
            $this->warn("{$fixes} time(s) would be fixed. Run again with --apply to save (make a backup first).");
        }

        return self::SUCCESS;
    }

    private function offsetSeconds(string $zone): int
    {
        return CarbonImmutable::now($zone)->getOffset();
    }
}
