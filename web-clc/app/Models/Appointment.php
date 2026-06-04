<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'appointment_date' => 'date',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'queue_number' => 'integer',
    ];

    public const STATUSES = ['booked', 'checked_in', 'in_progress', 'completed', 'cancelled'];

    public const TRANSITIONS = [
        'booked' => ['checked_in', 'cancelled'],
        'checked_in' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function transitionTo(string $status): void
    {
        if (! $this->canTransitionTo($status)) {
            abort(422, "Transisi status {$this->status} ke {$status} tidak valid");
        }

        $data = ['status' => $status];

        if ($status === 'checked_in') {
            $data['checked_in_at'] = now();
        }

        if ($status === 'completed') {
            $data['completed_at'] = now();
        }

        $this->update($data);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'doctor_schedule_id');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }
}
