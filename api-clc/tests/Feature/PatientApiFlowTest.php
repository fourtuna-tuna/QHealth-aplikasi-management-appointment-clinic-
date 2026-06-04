<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Service;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PatientApiFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_patient_auth_booking_check_in_and_payment_visibility_flow(): void
    {
        $service = Service::create([
            'name' => 'Test Poli',
            'code' => 'TST'.random_int(100, 999),
            'description' => 'Testing',
            'duration_minutes' => 20,
            'price' => 50000,
            'is_active' => true,
        ]);

        $doctor = Doctor::create([
            'service_id' => $service->id,
            'name' => 'dr. Test '.random_int(100, 999),
            'specialization' => 'Testing',
            'is_active' => true,
        ]);

        $schedule = DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'quota' => 5,
            'is_active' => true,
        ]);

        $this->getJson('/api/patient/appointments')->assertUnauthorized();

        $email = 'patient'.time().random_int(1000, 9999).'@example.test';
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Patient Test',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->json('data');

        $this->assertNotEmpty($register['token']);

        $login = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->assertOk()->json('data');

        $token = $login['token'];

        $appointment = $this->withToken($token)->postJson('/api/patient/appointments', [
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'complaint' => 'Keluhan test',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'booked')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->json('data');

        $this->withToken($token)->patchJson("/api/patient/appointments/{$appointment['id']}/check-in")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->withToken($token)->getJson('/api/patient/queue')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->withToken($token)->getJson('/api/patient/histories')
            ->assertOk()
            ->assertJsonPath('data.0.appointment.id', $appointment['id']);

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->refreshApplication();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }
}
