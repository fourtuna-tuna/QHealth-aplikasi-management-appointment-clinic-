<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResetUserActivity extends Command
{
    protected $signature = 'qhealth:reset-user-activity
        {--all : Reset activity for every user with activity}
        {--duplicates : Reset only users with more than one appointment}
        {--logout : Also delete affected users personal access tokens}
        {--force : Run without confirmation}
        {--dry-run : Show what would be deleted without deleting anything}';

    protected $description = 'Reset QHealth user activity without deleting user profiles or master data.';

    public function handle(): int
    {
        $resetAll = (bool) $this->option('all');
        $duplicatesOnly = ! $resetAll;

        if (! Schema::hasTable('appointments')) {
            $this->warn('Table appointments tidak ditemukan.');

            return self::SUCCESS;
        }

        $userIds = $this->targetUserIds($duplicatesOnly);

        if ($userIds->isEmpty()) {
            $this->info($duplicatesOnly
                ? 'Tidak ada user dengan appointment lebih dari satu.'
                : 'Tidak ada aktivitas user yang perlu di-reset.');

            return self::SUCCESS;
        }

        $appointmentIds = DB::table('appointments')
            ->whereIn('user_id', $userIds)
            ->pluck('id');

        $summary = [
            'users' => $userIds->count(),
            'appointments' => $appointmentIds->count(),
            'medical_records' => $this->medicalRecordCount($userIds, $appointmentIds),
            'queue_resets' => $resetAll && Schema::hasTable('queue_resets') ? DB::table('queue_resets')->count() : 0,
            'tokens' => $this->option('logout') && Schema::hasTable('personal_access_tokens')
                ? DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')->whereIn('tokenable_id', $userIds)->count()
                : 0,
        ];

        $this->table(['Data', 'Jumlah'], collect($summary)->map(fn ($count, $label) => [$label, $count])->all());

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Lanjut reset aktivitas user di atas?')) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $backupPath = $this->backup($userIds, $appointmentIds, $resetAll);

        DB::transaction(function () use ($userIds, $appointmentIds, $resetAll) {
            $this->deleteMedicalRecords($userIds, $appointmentIds);

            DB::table('appointments')->whereIn('id', $appointmentIds)->delete();

            if ($resetAll && Schema::hasTable('queue_resets')) {
                DB::table('queue_resets')->delete();
            }

            if ($this->option('logout') && Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
            }
        });

        $this->info("Aktivitas user berhasil di-reset. Backup: {$backupPath}");

        return self::SUCCESS;
    }

    private function targetUserIds(bool $duplicatesOnly)
    {
        $query = DB::table('appointments')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        if ($duplicatesOnly) {
            $query->havingRaw('COUNT(*) > 1');
        } else {
            $query->havingRaw('COUNT(*) >= 1');
        }

        return $query->pluck('user_id');
    }

    private function medicalRecordCount($userIds, $appointmentIds): int
    {
        if (! Schema::hasTable('medical_records')) {
            return 0;
        }

        return $this->medicalRecordQuery($userIds, $appointmentIds)->count();
    }

    private function deleteMedicalRecords($userIds, $appointmentIds): void
    {
        if (! Schema::hasTable('medical_records')) {
            return;
        }

        $this->medicalRecordQuery($userIds, $appointmentIds)->delete();
    }

    private function medicalRecordQuery($userIds, $appointmentIds)
    {
        return DB::table('medical_records')
            ->where(function ($query) use ($userIds, $appointmentIds) {
                if (Schema::hasColumn('medical_records', 'appointment_id')) {
                    $query->orWhereIn('appointment_id', $appointmentIds);
                }

                if (Schema::hasColumn('medical_records', 'user_id')) {
                    $query->orWhereIn('user_id', $userIds);
                }

                if (Schema::hasColumn('medical_records', 'patient_id')) {
                    $query->orWhereIn('patient_id', $userIds);
                }
            });
    }

    private function backup($userIds, $appointmentIds, bool $resetAll): string
    {
        $timestamp = now()->format('Ymd_His');
        $directory = "activity-reset-backups/{$timestamp}";

        $payloads = [
            'users.json' => Schema::hasTable('users')
                ? DB::table('users')->whereIn('id', $userIds)->get()
                : collect(),
            'appointments.json' => DB::table('appointments')->whereIn('id', $appointmentIds)->get(),
            'medical_records.json' => Schema::hasTable('medical_records')
                ? $this->medicalRecordQuery($userIds, $appointmentIds)->get()
                : collect(),
            'queue_resets.json' => $resetAll && Schema::hasTable('queue_resets')
                ? DB::table('queue_resets')->get()
                : collect(),
        ];

        foreach ($payloads as $file => $payload) {
            Storage::disk('local')->put(
                "{$directory}/{$file}",
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        return "storage/app/{$directory}";
    }
}
