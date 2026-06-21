<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
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
        return ((clone queue_base_query($appointmentDate))
            ->whereIn('status', queue_number_statuses())
            ->max('queue_number') ?? 0) + 1;
    }

    function active_queue_statuses(): array
    {
        return ['booked', 'pending', 'checked_in', 'in_queue', 'in_progress'];
    }

    function recordable_appointment_statuses(): array
    {
        return ['checked_in', 'in_progress'];
    }

    function queue_number_statuses(): array
    {
        return ['booked', 'pending', 'checked_in', 'in_queue', 'in_progress', 'completed', 'paid'];
    }

    function patient_has_active_appointment(User $user): bool
    {
        return $user->appointments()
            ->whereIn('status', active_queue_statuses())
            ->exists();
    }

    function waiting_check_in_statuses(): array
    {
        return ['booked', 'pending'];
    }

    function offline_registration_note(): string
    {
        return 'Pendaftaran offline dari panel admin';
    }

    function payable_appointments_query(): \Illuminate\Database\Eloquent\Builder
    {
        return Appointment::with(['patient', 'doctor.service', 'schedule', 'medicalRecord'])
            ->whereNotIn('status', ['cancelled', 'reset'])
            ->where(function ($query) {
                $query->where('payment_status', 'paid')
                    ->orWhereIn('status', ['completed', 'paid']);
            });
    }

    function recordable_appointments_query(): \Illuminate\Database\Eloquent\Builder
    {
        return Appointment::with(['patient', 'doctor.service', 'schedule'])
            ->whereNotNull('user_id')
            ->whereIn('status', recordable_appointment_statuses())
            ->where('payment_status', '!=', 'paid')
            ->whereDoesntHave('medicalRecord');
    }

    function auto_cancel_expired_appointments(): int
    {
        $minutes = max(1, (int) env('AUTO_CANCEL_MINUTES', 30));
        $now = now();
        $cancelled = 0;

        Appointment::with(['schedule', 'medicalRecord'])
            ->whereIn('status', waiting_check_in_statuses())
            ->where('payment_status', '!=', 'paid')
            ->whereNull('checked_in_at')
            ->whereDate('appointment_date', '<=', $now->toDateString())
            ->whereDoesntHave('medicalRecord')
            ->chunkById(100, function ($appointments) use ($minutes, $now, &$cancelled) {
                foreach ($appointments as $appointment) {
                    $startTime = $appointment->schedule?->start_time;

                    if (! $appointment->appointment_date || ! $startTime) {
                        continue;
                    }

                    $deadline = Carbon::parse($appointment->appointment_date->toDateString().' '.substr($startTime, 0, 5))
                        ->addMinutes($minutes);

                    if ($deadline->lte($now)) {
                        $appointment->forceFill(['status' => 'cancelled'])->save();
                        $cancelled++;
                    }
                }
            });

        return $cancelled;
    }
}

if (! function_exists('patient_role_values')) {
    function patient_role_values(): array
    {
        return ['patient', 'pasien'];
    }

    function patient_accounts_query(): \Illuminate\Database\Eloquent\Builder
    {
        return User::whereIn('role', patient_role_values());
    }
}

if (! function_exists('current_admin')) {
    function current_admin(Request $request): ?User
    {
        return User::where('role', 'admin')->find($request->session()->get('admin_id'));
    }

    function clinic_profile(): array
    {
        $defaults = [
            'name' => 'QHealth Clinic',
            'phone' => null,
            'address' => null,
        ];

        if (! Storage::disk('local')->exists('clinic-profile.json')) {
            return $defaults;
        }

        $profile = json_decode(Storage::disk('local')->get('clinic-profile.json'), true);

        return is_array($profile) ? array_merge($defaults, $profile) : $defaults;
    }

    function save_clinic_profile(array $profile): void
    {
        Storage::disk('local')->put('clinic-profile.json', json_encode($profile, JSON_PRETTY_PRINT));
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
        if (in_array($appointment->status, ['cancelled', 'reset'], true)) {
            return;
        }

        $data = [
            'status' => 'completed',
            'completed_at' => $appointment->completed_at ?? now(),
        ];

        if (Schema::hasColumn('appointments', 'payment_status')) {
            $data['payment_status'] = 'paid';
        }

        if (Schema::hasColumn('appointments', 'paid_at')) {
            $data['paid_at'] = $appointment->paid_at ?? now();
        }

        $appointment->forceFill($data)->save();
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
    Auth::logout();

    $request->session()->forget(['admin_id', 'admin_name']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware('admin')->group(function () {
    Route::get('/admin', function () {
        auto_cancel_expired_appointments();

        $appointments = Appointment::with(['patient', 'doctor.service', 'schedule', 'medicalRecord'])->latest('appointment_date')->latest()->get();
        $visitAppointments = Appointment::with(['patient', 'doctor.service', 'schedule', 'medicalRecord'])
            ->whereNotNull('user_id')
            ->whereHas('patient', fn ($query) => $query->whereIn('role', patient_role_values()))
            ->whereIn('status', active_queue_statuses())
            ->where('payment_status', '!=', 'paid')
            ->latest('appointment_date')
            ->latest()
            ->get();
        $patients = patient_accounts_query()
            ->with([
                'medicalRecords.doctor.service',
                'medicalRecords.appointment.doctor.service',
            ])
            ->withCount('appointments')
            ->latest()
            ->get();
        $activeDoctors = Doctor::with('service')->where('is_active', true)->latest()->get();
        $activeSchedules = DoctorSchedule::with('doctor.service')
            ->where('is_active', true)
            ->whereHas('doctor', fn ($query) => $query->where('is_active', true))
            ->orderBy('doctor_id')
            ->get();
        $activeAppointments = (clone $appointments)
            ->whereIn('status', active_queue_statuses())
            ->where('payment_status', '!=', 'paid')
            ->filter(fn ($appointment) => ! $appointment->medicalRecord);
        $registrationAppointments = Appointment::with(['patient', 'doctor.service', 'schedule'])
            ->where('notes', offline_registration_note())
            ->whereIn('status', waiting_check_in_statuses())
            ->where('payment_status', '!=', 'paid')
            ->latest('appointment_date')
            ->latest()
            ->get();
        $paidAppointments = payable_appointments_query()->latest('paid_at')->latest('completed_at')->latest()->get();
        $recordableAppointments = recordable_appointments_query()->latest('appointment_date')->latest()->get();
        $incomeTotal = $paidAppointments->sum(fn ($appointment) => (int) ($appointment->doctor?->service?->price ?? 0));
        $currentYear = now()->year;
        $currentMonth = now()->month;
        $monthlyAppointmentCounts = [];
        $monthlyIncomeTotals = [];

        foreach (range(1, 12) as $month) {
            $monthlyAppointmentCounts[$month] = $appointments
                ->filter(fn ($appointment) => $appointment->appointment_date?->year === $currentYear && $appointment->appointment_date?->month === $month)
                ->count();
            $monthlyIncomeTotals[$month] = $paidAppointments
                ->filter(fn ($appointment) => $appointment->appointment_date?->year === $currentYear && $appointment->appointment_date?->month === $month)
                ->sum(fn ($appointment) => (int) ($appointment->doctor?->service?->price ?? 0));
        }

        $monthlyIncomeTotal = $monthlyIncomeTotals[$currentMonth] ?? 0;

        return view('admin', [
            'services' => Service::withCount('doctors')->latest()->get(),
            'doctors' => Doctor::with(['service', 'schedules'])->latest()->get(),
            'schedules' => DoctorSchedule::with('doctor.service')->orderBy('doctor_id')->get(),
            'activeDoctors' => $activeDoctors,
            'activeSchedules' => $activeSchedules,
            'patients' => $patients,
            'appointments' => $appointments,
            'visitAppointments' => $visitAppointments,
            'activeAppointments' => $activeAppointments,
            'registrationAppointments' => $registrationAppointments,
            'paidAppointments' => $paidAppointments,
            'recordableAppointments' => $recordableAppointments,
            'monthlyAppointmentCounts' => $monthlyAppointmentCounts,
            'monthlyIncomeTotals' => $monthlyIncomeTotals,
            'records' => MedicalRecord::with(['patient', 'doctor.service', 'appointment'])->latest('visited_at')->get(),
            'stats' => [
                'patients' => patient_accounts_query()->count(),
                'doctors' => $activeDoctors->count(),
                'services' => Service::count(),
                'appointments' => Appointment::count(),
                'appointments_today' => Appointment::whereDate('appointment_date', now()->toDateString())
                    ->whereIn('status', active_queue_statuses())
                    ->where('payment_status', '!=', 'paid')
                    ->count(),
                'appointments_active' => $activeAppointments->count(),
                'payments_paid' => $paidAppointments->count(),
                'payments_unpaid' => Appointment::where('payment_status', 'unpaid')->count(),
                'income_total' => $incomeTotal,
                'income_month' => $monthlyIncomeTotal,
                'records' => MedicalRecord::count(),
            ],
        ]);
    })->name('admin');

    Route::get('/admin/pasien', fn () => redirect('/admin#pasien'));

    Route::get('/admin/profile', fn (Request $request) => view('admin-profile', [
        'admin' => current_admin($request),
    ]))->name('admin.profile');

    Route::get('/admin/settings', fn (Request $request) => view('admin-settings', [
        'admin' => current_admin($request),
        'clinic' => clinic_profile(),
    ]))->name('admin.settings');

    Route::post('/admin/settings/account', function (Request $request) {
        $admin = current_admin($request) ?? abort(403);
        $data = $request->validate([
            'name' => ['required', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($admin->id)],
        ]);

        $admin->update($data);
        $request->session()->put('admin_name', $admin->name);

        return back()->with('status', 'Akun admin diperbarui');
    })->name('admin.settings.account');

    Route::post('/admin/settings/password', function (Request $request) {
        $admin = current_admin($request) ?? abort(403);
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai']);
        }

        $admin->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('status', 'Password admin diperbarui');
    })->name('admin.settings.password');

    Route::post('/admin/settings/clinic', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'max:120'],
            'phone' => ['nullable', 'max:30'],
            'address' => ['nullable', 'max:255'],
        ]);

        save_clinic_profile($data);

        return back()->with('status', 'Profil klinik diperbarui');
    })->name('admin.settings.clinic');

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
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
        ]);

        if (blank($data['email'] ?? null)) {
            $existingUser = $request->filled('id') ? patient_accounts_query()->find($request->input('id')) : null;
            $data['email'] = $existingUser && str_ends_with($existingUser->email, '@qhealth.local')
                ? $existingUser->email
                : 'offline+'.now()->format('YmdHis').Str::random(6).'@qhealth.local';
        }

        if ($request->filled('id')) {
            patient_accounts_query()->findOrFail($request->input('id'))->update($data);
        } else {
            User::create($data + [
                'role' => 'patient',
                'password' => Str::random(40),
            ]);
        }

        return back()->with('status', 'Pasien tersimpan');
    });

    Route::get('/admin/pasien/search', function (Request $request) {
        $keyword = trim((string) $request->query('q', ''));

        if (strlen($keyword) < 2) {
            return response()->json([]);
        }

        $patients = patient_accounts_query()
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");

                if (ctype_digit($keyword)) {
                    $query->orWhere('id', (int) ltrim($keyword, '0'));
                }
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'birth_date', 'gender', 'address']);

        return response()->json($patients->map(fn (User $patient) => [
            'id' => $patient->id,
            'no_rm' => str_pad($patient->id, 6, '0', STR_PAD_LEFT),
            'name' => $patient->name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'birth_date' => $patient->birth_date?->format('d-m-Y'),
            'gender' => $patient->gender,
            'address' => $patient->address,
        ]));
    })->name('admin.patients.search');

    Route::post('/admin/offline-appointments', function (Request $request) {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', patient_role_values()))],
            'doctor_id' => ['required', Rule::exists('doctors', 'id')->where('is_active', true)],
            'doctor_schedule_id' => ['required', Rule::exists('doctor_schedules', 'id')->where('is_active', true)],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'complaint' => ['required', 'string', 'max:500'],
        ]);

        $patient = patient_accounts_query()->findOrFail($data['user_id']);

        $schedule = DoctorSchedule::where('doctor_id', $data['doctor_id'])->findOrFail($data['doctor_schedule_id']);
        auto_cancel_expired_appointments();

        if (patient_has_active_appointment($patient)) {
            return back()->withErrors(['appointment' => 'Anda masih memiliki antrean aktif. Selesaikan antrean sebelumnya terlebih dahulu.'])->withInput();
        }

        $queueNumber = next_queue_number($data['appointment_date']);

        $activeOnSchedule = (clone queue_base_query($data['appointment_date']))
            ->where('doctor_schedule_id', $schedule->id)
            ->whereIn('status', active_queue_statuses())
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
            'notes' => offline_registration_note(),
            'queue_number' => $queueNumber,
            'status' => 'booked',
            'payment_status' => 'unpaid',
        ]);

        return back()->with('status', "Pasien offline masuk antrean nomor {$queueNumber}");
    });

    Route::post('/admin/queue/reset', function () {
        auto_cancel_expired_appointments();

        Appointment::whereDate('appointment_date', now()->toDateString())
            ->whereIn('status', active_queue_statuses())
            ->where('payment_status', '!=', 'paid')
            ->whereDoesntHave('medicalRecord')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        DB::table('queue_resets')->insert([
            'reset_date' => now()->toDateString(),
            'reset_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Antrean aktif hari ini direset dan dibatalkan. Nomor antrean berikutnya mulai dari 1.');
    });

    Route::post('/admin/appointments/{appointment}/status', function (Request $request, Appointment $appointment) {
        $data = $request->validate(['status' => ['required', Rule::in(Appointment::STATUSES)]]);
        $appointment->transitionTo($data['status']);

        if ($data['status'] === 'completed') {
            complete_visit($appointment->fresh(['doctor.service']));
        }

        return back()->with('status', 'Status kunjungan diperbarui');
    });

    Route::post('/admin/appointments/{appointment}/payment', function (Request $request, Appointment $appointment) {
        $data = $request->validate(['payment_status' => ['required', 'in:unpaid,paid']]);

        if ($data['payment_status'] === 'paid') {
            abort_unless(in_array($appointment->status, ['checked_in', 'in_progress'], true), 422, 'Pasien harus check-in sebelum ditandai selesai.');
        }

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
        abort_if(! $appointment->user_id, 422, 'Appointment belum terhubung ke pasien.');
        abort_if(in_array($appointment->status, ['cancelled', 'reset'], true), 422, 'Appointment yang dibatalkan atau direset tidak bisa dibuatkan rekam medis.');

        $existingRecord = MedicalRecord::where('appointment_id', $appointment->id)->first();
        abort_if(
            ! $existingRecord && ! in_array($appointment->status, recordable_appointment_statuses(), true),
            422,
            'Appointment harus check-in atau dalam proses pelayanan sebelum dibuatkan rekam medis.'
        );

        $recordKey = $existingRecord
            ? ['id' => $existingRecord->id]
            : ($request->filled('id') ? ['id' => $request->input('id')] : ['appointment_id' => $data['appointment_id']]);

        MedicalRecord::updateOrCreate(
            $recordKey,
            $data + ['user_id' => $appointment->user_id, 'doctor_id' => $appointment->doctor_id]
        );

        complete_visit($appointment->fresh(['doctor.service']));

        return back()->with('status', 'Rekam medis tersimpan');
    });

    Route::delete('/admin/{type}/{id}', function (string $type, int $id) {
        match ($type) {
            'services' => Service::findOrFail($id)->delete(),
            'doctors' => Doctor::findOrFail($id)->delete(),
            'schedules' => DoctorSchedule::findOrFail($id)->delete(),
            'patients' => patient_accounts_query()->findOrFail($id)->delete(),
            default => abort(404),
        };

        return back()->with('status', 'Data dihapus');
    });
});
