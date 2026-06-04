<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::get('/', fn () => redirect('/admin'));

Route::get('/login', fn () => view('login'))->name('login');

if (! function_exists('next_queue_number')) {
    function latest_queue_reset_at(string $appointmentDate): ?\Illuminate\Support\Carbon
    {
        if (! Schema::hasTable('queue_resets')) {
            return null;
        }

        $resetAt = DB::table('queue_resets')
            ->whereDate('reset_date', $appointmentDate)
            ->latest('reset_at')
            ->value('reset_at');

        return $resetAt ? \Illuminate\Support\Carbon::parse($resetAt) : null;
    }

    function queue_base_query(string $appointmentDate): \Illuminate\Database\Eloquent\Builder
    {
        $query = Appointment::whereDate('appointment_date', $appointmentDate);
        $resetAt = latest_queue_reset_at($appointmentDate);

        if ($resetAt) {
            $query->where('created_at', '>', $resetAt);
        }

        return $query;
    }

    function next_queue_number(string $appointmentDate): int
    {
        return ((clone queue_base_query($appointmentDate))->max('queue_number') ?? 0) + 1;
    }
}

if (! function_exists('ensure_visit_history')) {
    function ensure_visit_history(Appointment $appointment): void
    {
        if ($appointment->status === 'cancelled' || $appointment->medicalRecord()->exists()) {
            return;
        }

        MedicalRecord::create([
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->user_id,
            'doctor_id' => $appointment->doctor_id,
            'diagnosis' => 'Kunjungan selesai',
            'treatment' => $appointment->doctor?->service?->name,
            'doctor_notes' => $appointment->notes,
            'visited_at' => $appointment->appointment_date ?? now()->toDateString(),
        ]);
    }

    function complete_visit(Appointment $appointment): void
    {
        if (! in_array($appointment->status, ['completed', 'cancelled'], true)) {
            $appointment->forceFill([
                'status' => 'completed',
                'completed_at' => $appointment->completed_at ?? now(),
            ])->save();
        }

        $appointment->refresh();
        ensure_visit_history($appointment);
    }
}

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'login' => ['required', 'string'],
        'password' => ['required'],
    ]);

    $login = $credentials['login'];
    $account = User::where('role', 'admin')
        ->where(fn ($query) => $query->where('email', $login)->orWhere('name', $login))
        ->first();

    if (! $account || ! Hash::check($credentials['password'], $account->password)) {
        return back()->withErrors(['login' => 'Username/email atau password admin salah'])->onlyInput('login');
    }

    $request->session()->regenerate();
    $request->session()->put('admin_id', $account->id);
    $request->session()->put('admin_name', $account->name);

    return redirect()->route('admin');
});

Route::post('/logout', function (Request $request) {
    $request->session()->forget(['admin_id', 'admin_name']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware('admin')->group(function () {
    Route::get('/admin', function () {
        $appointments = Appointment::with(['patient', 'doctor.service', 'schedule', 'medicalRecord'])->latest('appointment_date')->latest()->get();
        $patients = User::where('role', 'patient')->withCount('appointments')->latest()->get();
        $paidAppointments = (clone $appointments)->where('payment_status', 'paid');
        $incomeTotal = $paidAppointments->sum(fn ($appointment) => (int) ($appointment->doctor?->service?->price ?? 0));

        return view('admin', [
            'services' => Service::withCount('doctors')->latest()->get(),
            'doctors' => Doctor::with(['service', 'schedules'])->latest()->get(),
            'schedules' => DoctorSchedule::with('doctor.service')->orderBy('doctor_id')->get(),
            'patients' => $patients,
            'appointments' => $appointments,
            'records' => MedicalRecord::with(['patient', 'doctor.service', 'appointment'])->latest('visited_at')->get(),
            'stats' => [
                'patients' => User::where('role', 'patient')->count(),
                'doctors' => Doctor::count(),
                'services' => Service::count(),
                'appointments' => Appointment::count(),
                'appointments_today' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
                'appointments_active' => Appointment::whereIn('status', ['booked', 'checked_in', 'in_progress'])->count(),
                'payments_paid' => Appointment::where('payment_status', 'paid')->count(),
                'payments_unpaid' => Appointment::where('payment_status', 'unpaid')->count(),
                'income_total' => $incomeTotal,
                'records' => MedicalRecord::count(),
            ],
        ]);
    })->name('admin');

    Route::post('/admin/services', function (Request $request) {
        Service::updateOrCreate(
            ['id' => $request->input('id')],
            $request->validate([
                'id' => ['nullable', 'exists:services,id'],
                'name' => ['required', 'max:120'],
                'code' => ['required', 'max:20', Rule::unique('services')->ignore($request->input('id'))],
                'description' => ['nullable'],
                'duration_minutes' => ['required', 'integer', 'min:5'],
                'price' => ['required', 'integer', 'min:0'],
                'is_active' => ['nullable'],
            ]) + ['is_active' => $request->boolean('is_active')]
        );

        return back()->with('status', 'Layanan tersimpan');
    });

    Route::post('/admin/doctors', function (Request $request) {
        Doctor::updateOrCreate(
            ['id' => $request->input('id')],
            $request->validate([
                'id' => ['nullable', 'exists:doctors,id'],
                'service_id' => ['required', 'exists:services,id'],
                'name' => ['required', 'max:120'],
                'specialization' => ['required', 'max:120'],
                'sip_number' => ['nullable', 'max:80'],
                'phone' => ['nullable', 'max:30'],
                'bio' => ['nullable'],
                'is_active' => ['nullable'],
            ]) + ['is_active' => $request->boolean('is_active')]
        );

        return back()->with('status', 'Dokter tersimpan');
    });

    Route::post('/admin/schedules', function (Request $request) {
        DoctorSchedule::updateOrCreate(
            ['id' => $request->input('id')],
            $request->validate([
                'id' => ['nullable', 'exists:doctor_schedules,id'],
                'doctor_id' => ['required', 'exists:doctors,id'],
                'day' => ['required', 'max:20'],
                'start_time' => ['required'],
                'end_time' => ['required', 'after:start_time'],
                'quota' => ['required', 'integer', 'min:1'],
                'is_active' => ['nullable'],
            ]) + ['is_active' => $request->boolean('is_active')]
        );

        return back()->with('status', 'Jadwal tersimpan');
    });

    Route::post('/admin/patients', function (Request $request) {
        $data = $request->validate([
            'id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'max:120'],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('users')->ignore($request->input('id'))],
            'phone' => ['nullable', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'max:20'],
            'address' => ['nullable'],
            'blood_type' => ['nullable', 'max:5'],
        ]);

        if (blank($data['email'] ?? null)) {
            $existingUser = $request->filled('id') ? User::where('role', 'patient')->find($request->input('id')) : null;
            $data['email'] = $existingUser && str_ends_with($existingUser->email, '@qhealth.local')
                ? $existingUser->email
                : 'offline+'.now()->format('YmdHis').Str::random(6).'@qhealth.local';
        }

        if ($request->filled('id')) {
            User::where('role', 'patient')->findOrFail($request->input('id'))->update($data);
        } else {
            User::create($data + [
                'role' => 'patient',
                'password' => Str::random(40),
            ]);
        }

        return back()->with('status', 'Pasien tersimpan');
    });

    Route::post('/admin/offline-appointments', function (Request $request) {
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'patient')],
            'patient_name' => ['required_without:user_id', 'nullable', 'max:120'],
            'patient_email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'patient_phone' => ['nullable', 'max:30'],
            'patient_birth_date' => ['nullable', 'date'],
            'patient_gender' => ['nullable', 'max:20'],
            'patient_address' => ['nullable'],
            'patient_blood_type' => ['nullable', 'max:5'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'doctor_schedule_id' => ['required', 'exists:doctor_schedules,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'complaint' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ]);

        if (filled($data['user_id'] ?? null)) {
            $patient = User::where('role', 'patient')->findOrFail($data['user_id']);
        } else {
            $patient = User::create([
                'name' => $data['patient_name'],
                'email' => filled($data['patient_email'] ?? null)
                    ? $data['patient_email']
                    : 'offline+'.now()->format('YmdHis').Str::random(6).'@qhealth.local',
                'role' => 'patient',
                'password' => Str::random(40),
                'phone' => $data['patient_phone'] ?? null,
                'birth_date' => $data['patient_birth_date'] ?? null,
                'gender' => $data['patient_gender'] ?? null,
                'address' => $data['patient_address'] ?? null,
                'blood_type' => $data['patient_blood_type'] ?? null,
            ]);
        }

        $schedule = DoctorSchedule::where('doctor_id', $data['doctor_id'])->findOrFail($data['doctor_schedule_id']);
        $queueNumber = next_queue_number($data['appointment_date']);
        $activeOnSchedule = (clone queue_base_query($data['appointment_date']))
            ->where('doctor_schedule_id', $schedule->id)
            ->whereIn('status', ['booked', 'checked_in', 'in_progress'])
            ->count();

        if ($activeOnSchedule >= $schedule->quota) {
            return back()->withErrors(['appointment' => 'Kuota dokter pada tanggal tersebut sudah penuh'])->withInput();
        }

        Appointment::create([
            'user_id' => $patient->id,
            'doctor_id' => $data['doctor_id'],
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => $data['appointment_date'],
            'complaint' => $data['complaint'],
            'notes' => $data['notes'] ?? null,
            'queue_number' => $queueNumber,
            'status' => 'checked_in',
            'checked_in_at' => now(),
            'payment_status' => 'unpaid',
        ]);

        return back()->with('status', "Pasien offline masuk antrean nomor {$queueNumber}");
    });

    Route::post('/admin/queue/reset', function () {
        DB::table('queue_resets')->insert([
            'reset_date' => now()->toDateString(),
            'reset_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Antrean hari ini direset. Nomor antrean berikutnya mulai dari 1.');
    });

    Route::post('/admin/appointments/{appointment}/status', function (Request $request, Appointment $appointment) {
        $data = $request->validate(['status' => ['required', Rule::in(Appointment::STATUSES)]]);
        $appointment->transitionTo($data['status']);

        if ($data['status'] === 'completed') {
            ensure_visit_history($appointment->fresh(['doctor.service']));
        }

        return back()->with('status', 'Status kunjungan diperbarui');
    });

    Route::post('/admin/appointments/{appointment}/payment', function (Request $request, Appointment $appointment) {
        $data = $request->validate(['payment_status' => ['required', 'in:unpaid,paid']]);
        $appointment->update([
            'payment_status' => $data['payment_status'],
            'paid_at' => $data['payment_status'] === 'paid' ? now() : null,
        ]);

        if ($data['payment_status'] === 'paid') {
            complete_visit($appointment->fresh(['doctor.service']));
        }

        return back()->with('status', 'Status pembayaran diperbarui');
    });

    Route::post('/admin/records', function (Request $request) {
        $data = $request->validate([
            'id' => ['nullable', 'exists:medical_records,id'],
            'appointment_id' => ['required', 'exists:appointments,id'],
            'diagnosis' => ['required', 'max:200'],
            'prescription' => ['nullable'],
            'treatment' => ['nullable'],
            'doctor_notes' => ['nullable'],
            'visited_at' => ['required', 'date'],
        ]);
        $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($data['appointment_id']);

        MedicalRecord::updateOrCreate(
            ['id' => $request->input('id')],
            $data + ['user_id' => $appointment->user_id, 'doctor_id' => $appointment->doctor_id]
        );

        if ($appointment->status === 'in_progress') {
            $appointment->transitionTo('completed');
        }

        return back()->with('status', 'Rekam medis tersimpan');
    });

    Route::delete('/admin/{type}/{id}', function (string $type, int $id) {
        match ($type) {
            'services' => Service::findOrFail($id)->delete(),
            'doctors' => Doctor::findOrFail($id)->delete(),
            'schedules' => DoctorSchedule::findOrFail($id)->delete(),
            'patients' => User::where('role', 'patient')->findOrFail($id)->delete(),
            default => abort(404),
        };

        return back()->with('status', 'Data dihapus');
    });
});
