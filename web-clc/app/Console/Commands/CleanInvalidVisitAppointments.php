<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CleanInvalidVisitAppointments extends Command
{
    protected $signature = 'qhealth:clean-invalid-visits
        {--force : Run without confirmation}
        {--dry-run : Show what would be deleted without deleting anything}';

    protected $description = 'Clean invalid visit-report appointments without deleting patient accounts or master data.';

    public function handle(): int
    {
        if (! Schema::hasTable('appointments')) {
            $this->warn('Table appointments tidak ditemukan.');

            return self::SUCCESS;
        }

        $appointmentIds = $this->invalidAppointmentIds();

        if ($appointmentIds->isEmpty()) {
            $this->info('Tidak ada appointment invalid/orphan yang perlu dibersihkan.');

            return self::SUCCESS;
        }

        $medicalRecordCount = Schema::hasTable('medical_records')
            ? $this->medicalRecordQuery($appointmentIds)->count()
            : 0;

        $this->table(['Data', 'Jumlah'], [
            ['appointments invalid/orphan', $appointmentIds->count()],
            ['medical_records terkait', $medicalRecordCount],
        ]);

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Lanjut hapus appointment invalid/orphan di atas?')) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $backupPath = $this->backup($appointmentIds);

        DB::transaction(function () use ($appointmentIds) {
            if (Schema::hasTable('medical_records')) {
                $this->medicalRecordQuery($appointmentIds)->delete();
            }

            DB::table('appointments')->whereIn('id', $appointmentIds)->delete();
        });

        $this->info("Data kunjungan invalid/orphan berhasil dibersihkan. Backup: {$backupPath}");

        return self::SUCCESS;
    }

    private function invalidAppointmentIds()
    {
        return DB::table('appointments')
            ->leftJoin('users', 'appointments.user_id', '=', 'users.id')
            ->where(function ($query) {
                $query->whereNull('appointments.user_id')
                    ->orWhereNull('users.id')
                    ->orWhereNotIn('users.role', ['patient', 'pasien'])
                    ->orWhereNull('appointments.appointment_date')
                    ->orWhereNull('appointments.queue_number');
            })
            ->pluck('appointments.id');
    }

    private function medicalRecordQuery($appointmentIds)
    {
        return DB::table('medical_records')
            ->whereIn('appointment_id', $appointmentIds);
    }

    private function backup($appointmentIds): string
    {
        $timestamp = now()->format('Ymd_His');
        $directory = "invalid-visit-cleanup-backups/{$timestamp}";

        Storage::disk('local')->put(
            "{$directory}/appointments.json",
            json_encode(DB::table('appointments')->whereIn('id', $appointmentIds)->get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if (Schema::hasTable('medical_records')) {
            Storage::disk('local')->put(
                "{$directory}/medical_records.json",
                json_encode($this->medicalRecordQuery($appointmentIds)->get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        return "storage/app/{$directory}";
    }
}
