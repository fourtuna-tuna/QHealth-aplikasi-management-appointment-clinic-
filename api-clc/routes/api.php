<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

if (! function_exists('api_ok')) {
    function api_ok(mixed $data = null, string $message = 'OK', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}

if (! function_exists('patient')) {
    function patient(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'patient', 403, 'Akses khusus pasien');

        return $user;
    }
}

if (! function_exists('latest_queue_reset_at')) {
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

    function active_queue_statuses(): array
    {
        return ['booked', 'pending', 'checked_in'];
    }

    function waiting_check_in_statuses(): array
    {
        return ['booked', 'pending'];
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
            'doctor_notes' => $appointment->complaint,
            'visited_at' => $appointment->appointment_date ?? now()->toDateString(),
        ]);
    }

    function complete_patient_visit(Appointment $appointment): void
    {
        $appointment->forceFill([
            'status' => 'completed',
            'checked_in_at' => $appointment->checked_in_at ?? now(),
            'completed_at' => $appointment->completed_at ?? now(),
        ])->save();

        ensure_visit_history($appointment->fresh(['doctor.service']));
    }
}

Route::get('/health', fn () => api_ok(['service' => 'CLC API', 'database' => config('database.default')]));

Route::post('/auth/register', function (Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160', 'unique:users,email'],
        'password' => ['required', 'confirmed', 'min:8'],
        'phone' => ['nullable', 'string', 'max:30'],
        'birth_date' => ['nullable', 'date'],
        'gender' => ['nullable', 'string', 'max:20'],
        'address' => ['nullable', 'string'],
        'blood_type' => ['nullable', 'string', 'max:5'],
    ]);

    $data['role'] = 'patient';
    $data['password'] = Hash::make($data['password']);
    $user = User::create($data);
    $token = $user->createToken('mobile')->plainTextToken;

    return api_ok(['user' => $user, 'token' => $token], 'Registrasi berhasil. Silakan login.', 201);
});

Route::post('/auth/login', function (Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('email', $data['email'])->where('role', 'patient')->first();

    if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
        return response()->json(['success' => false, 'message' => 'Email atau password salah'], 401);
    }

    $user->tokens()->delete();
    $token = $user->createToken('mobile')->plainTextToken;

    return api_ok(['user' => $user, 'token' => $token], 'Login berhasil');
});

Route::post('/auth/forgot-password', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email', Rule::exists('users', 'email')->where('role', 'patient')],
    ]);

    $status = Password::broker('users')->sendResetLink($request->only('email'));

    return $status === Password::RESET_LINK_SENT
        ? api_ok(null, __($status))
        : response()->json(['success' => false, 'message' => __($status)], 422);
});

Route::post('/auth/reset-password', function (Request $request) {
    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email', Rule::exists('users', 'email')->where('role', 'patient')],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $status = Password::broker('users')->reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            event(new PasswordReset($user));
        }
    );

    return $status === Password::PASSWORD_RESET
        ? api_ok(null, __($status))
        : response()->json(['success' => false, 'message' => __($status)], 422);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', function (Request $request) {
        $request->user()->tokens()->delete();

        return api_ok(null, 'Logout berhasil');
    });

    Route::get('/auth/me', fn (Request $request) => api_ok(patient($request)));

    Route::patch('/auth/profile', function (Request $request) {
        $user = patient($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'blood_type' => ['nullable', 'string', 'max:5'],
        ]);
        $user->update($data);

        return api_ok($user->fresh(), 'Profil berhasil diperbarui');
    });

    Route::post('/auth/change-password', function (Request $request) {
        $user = patient($request);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password lama tidak sesuai', 'data' => null], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();

        return api_ok(null, 'Password berhasil diperbarui. Silakan login kembali.');
    });

    Route::get('/patient/appointments', function (Request $request) {
        auto_cancel_expired_appointments();

        return api_ok(patient($request)->appointments()
            ->with(['doctor.service', 'schedule'])
            ->whereIn('status', active_queue_statuses())
            ->latest('appointment_date')
            ->latest('id')
            ->get());
    });

    Route::post('/patient/appointments', function (Request $request) {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'doctor_schedule_id' => ['required', 'exists:doctor_schedules,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'complaint' => ['required', 'string', 'max:500'],
        ]);

        $schedule = DoctorSchedule::where('doctor_id', $data['doctor_id'])->findOrFail($data['doctor_schedule_id']);
        auto_cancel_expired_appointments();

        $activeOnSchedule = (clone queue_base_query($data['appointment_date']))
            ->where('doctor_schedule_id', $schedule->id)
            ->whereIn('status', active_queue_statuses())
            ->count();

        if ($activeOnSchedule >= $schedule->quota) {
            return response()->json(['success' => false, 'message' => 'Kuota dokter pada tanggal tersebut sudah penuh'], 422);
        }

        $appointment = Appointment::create([
            ...$data,
            'user_id' => patient($request)->id,
            'queue_number' => next_queue_number($data['appointment_date']),
            'status' => 'booked',
            'payment_status' => 'unpaid',
        ])->load(['doctor.service', 'schedule']);

        return api_ok($appointment, 'Booking berhasil dibuat', 201);
    });

    Route::patch('/patient/appointments/{appointment}/check-in', function (Request $request, Appointment $appointment) {
        abort_unless($appointment->user_id === patient($request)->id, 403);
        abort(403, 'Check-in dilakukan oleh admin klinik.');
    });

    Route::delete('/patient/appointments/{appointment}', function (Request $request, Appointment $appointment) {
        abort_unless($appointment->user_id === patient($request)->id, 403);
        $appointment->transitionTo('cancelled');

        return api_ok($appointment->fresh(), 'Booking berhasil dibatalkan');
    });

    Route::get('/patient/queue', function (Request $request) {
        auto_cancel_expired_appointments();

        $today = now()->toDateString();
        $todayResetAt = latest_queue_reset_at($today);
        $appointment = patient($request)->appointments()
            ->with(['doctor.service', 'schedule'])
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', active_queue_statuses())
            ->when($todayResetAt, function ($query) use ($today, $todayResetAt) {
                $query->where(function ($inner) use ($today, $todayResetAt) {
                    $inner->whereDate('appointment_date', '!=', $today)
                        ->orWhere('created_at', '>', $todayResetAt);
                });
            })
            ->orderBy('appointment_date')
            ->orderBy('queue_number')
            ->first();

        if (! $appointment) {
            return api_ok(null, 'Tidak ada antrean aktif');
        }

        $queueDate = $appointment->appointment_date->toDateString();
        $activeQueue = (clone queue_base_query($queueDate))
            ->whereIn('status', active_queue_statuses());
        $currentQueue = (clone $activeQueue)->orderBy('queue_number')->value('queue_number') ?? 0;
        $remainingQueue = (clone $activeQueue)
            ->where('queue_number', '<', $appointment->queue_number)
            ->count();

        return api_ok([
            'appointment' => $appointment,
            'current_queue' => $currentQueue,
            'remaining_queue' => $remainingQueue,
        ]);
    });

    Route::get('/patient/histories', function (Request $request) {
        auto_cancel_expired_appointments();

        return api_ok(patient($request)->medicalRecords()
            ->with(['doctor.service', 'appointment'])
            ->latest('visited_at')
            ->get());
    });

    Route::get('/patient/histories/{medicalRecord}', function (Request $request, MedicalRecord $medicalRecord) {
        abort_unless($medicalRecord->user_id === patient($request)->id, 403);

        return api_ok($medicalRecord->load(['doctor.service', 'appointment']));
    });
});

Route::get('/services', fn () => api_ok(Service::where('is_active', true)->withCount('doctors')->get()));

Route::get('/doctors', function (Request $request) {
    $query = Doctor::with(['service', 'schedules' => fn ($q) => $q->where('is_active', true)])
        ->where('is_active', true);

    if ($request->filled('service_id')) {
        $query->where('service_id', $request->integer('service_id'));
    }

    return api_ok($query->get());
});

Route::get('/doctors/{doctor}/schedules', function (Doctor $doctor) {
    return api_ok($doctor->schedules()->where('is_active', true)->get());
});
