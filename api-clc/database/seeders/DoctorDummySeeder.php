<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DoctorDummySeeder extends Seeder
{
    public function run(): void
    {
        $services = collect([
            ['name' => 'Poli Umum', 'code' => 'UMU', 'description' => 'Konsultasi awal, keluhan harian, dan rujukan lanjutan.', 'duration_minutes' => 20, 'price' => 75000],
            ['name' => 'Poli Gigi', 'code' => 'GIG', 'description' => 'Pemeriksaan gigi, scaling, tambal, dan tindakan ringan.', 'duration_minutes' => 30, 'price' => 120000],
            ['name' => 'Poli Anak', 'code' => 'ANK', 'description' => 'Pemeriksaan anak, imunisasi, dan tumbuh kembang.', 'duration_minutes' => 25, 'price' => 90000],
        ])->mapWithKeys(fn (array $service) => [
            $service['code'] => Service::updateOrCreate(['code' => $service['code']], $service + ['is_active' => true]),
        ]);

        $doctors = [
            ['service' => 'UMU', 'name' => 'dr. Andi Pratama', 'specialization' => 'Dokter Umum', 'sip_number' => 'SIP-UMU-001', 'phone' => '081200100001'],
            ['service' => 'UMU', 'name' => 'dr. Budi Santoso', 'specialization' => 'Dokter Umum', 'sip_number' => 'SIP-UMU-002', 'phone' => '081200100002'],
            ['service' => 'UMU', 'name' => 'dr. Dimas Nugraha', 'specialization' => 'Dokter Umum', 'sip_number' => 'SIP-UMU-003', 'phone' => '081200100003'],
            ['service' => 'UMU', 'name' => 'dr. Fajar Ramadhan', 'specialization' => 'Dokter Umum', 'sip_number' => 'SIP-UMU-004', 'phone' => '081200100004'],
            ['service' => 'UMU', 'name' => 'dr. Rizky Mahendra', 'specialization' => 'Dokter Umum', 'sip_number' => 'SIP-UMU-005', 'phone' => '081200100005'],
            ['service' => 'GIG', 'name' => 'drg. Siti Rahma', 'specialization' => 'Dokter Gigi', 'sip_number' => 'SIP-GIG-001', 'phone' => '081200200001'],
            ['service' => 'GIG', 'name' => 'drg. Maya Putri', 'specialization' => 'Dokter Gigi', 'sip_number' => 'SIP-GIG-002', 'phone' => '081200200002'],
            ['service' => 'GIG', 'name' => 'drg. Nabila Safitri', 'specialization' => 'Dokter Gigi', 'sip_number' => 'SIP-GIG-003', 'phone' => '081200200003'],
            ['service' => 'GIG', 'name' => 'drg. Intan Lestari', 'specialization' => 'Dokter Gigi', 'sip_number' => 'SIP-GIG-004', 'phone' => '081200200004'],
            ['service' => 'GIG', 'name' => 'drg. Rina Kartika', 'specialization' => 'Dokter Gigi', 'sip_number' => 'SIP-GIG-005', 'phone' => '081200200005'],
            ['service' => 'ANK', 'name' => 'dr. Sarah Amelia, Sp.A', 'specialization' => 'Spesialis Anak', 'sip_number' => 'SIP-ANK-001', 'phone' => '081200300001'],
            ['service' => 'ANK', 'name' => 'dr. Nadia Putri, Sp.A', 'specialization' => 'Spesialis Anak', 'sip_number' => 'SIP-ANK-002', 'phone' => '081200300002'],
            ['service' => 'ANK', 'name' => 'dr. Reza Akbar, Sp.A', 'specialization' => 'Spesialis Anak', 'sip_number' => 'SIP-ANK-003', 'phone' => '081200300003'],
            ['service' => 'ANK', 'name' => 'dr. Dwi Kurniawan, Sp.A', 'specialization' => 'Spesialis Anak', 'sip_number' => 'SIP-ANK-004', 'phone' => '081200300004'],
            ['service' => 'ANK', 'name' => 'dr. Ahmad Fauzan, Sp.A', 'specialization' => 'Spesialis Anak', 'sip_number' => 'SIP-ANK-005', 'phone' => '081200300005'],
        ];

        foreach ($doctors as $index => $data) {
            $doctor = Doctor::updateOrCreate(
                ['name' => $data['name']],
                [
                    'service_id' => $services[$data['service']]->id,
                    'specialization' => $data['specialization'],
                    'sip_number' => $data['sip_number'],
                    'phone' => $data['phone'],
                    'bio' => 'Dokter aktif untuk layanan '.$services[$data['service']]->name.'.',
                    'is_active' => true,
                ]
            );

            foreach ($this->scheduleSet($index) as $schedule) {
                DoctorSchedule::updateOrCreate(
                    ['doctor_id' => $doctor->id, 'day' => $schedule['day']],
                    $schedule + ['quota' => 18, 'is_active' => true]
                );
            }
        }
    }

    private function scheduleSet(int $index): array
    {
        return match ($index % 3) {
            0 => [
                ['day' => 'Monday', 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day' => 'Wednesday', 'start_time' => '13:00', 'end_time' => '17:00'],
                ['day' => 'Friday', 'start_time' => '08:00', 'end_time' => '12:00'],
            ],
            1 => [
                ['day' => 'Tuesday', 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day' => 'Thursday', 'start_time' => '13:00', 'end_time' => '17:00'],
                ['day' => 'Saturday', 'start_time' => '08:00', 'end_time' => '11:00'],
            ],
            default => [
                ['day' => 'Monday', 'start_time' => '13:00', 'end_time' => '17:00'],
                ['day' => 'Wednesday', 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day' => 'Friday', 'start_time' => '13:00', 'end_time' => '17:00'],
            ],
        };
    }
}
