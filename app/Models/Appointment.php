<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'practice_id',
        'appointment_number',
        'appointment_date',
        'appointment_time',
        'type',
        'status',
        'reason',
        'notes',
        'fee',
        'payment_status',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'datetime:H:i',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'fee' => 'decimal:2',
            'payment_status' => 'boolean',
        ];
    }

    /**
     * Get the doctor for this appointment.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient for this appointment.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the practice for this appointment.
     */
    public function practice()
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the prescriptions for this appointment.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the medical records for this appointment.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
