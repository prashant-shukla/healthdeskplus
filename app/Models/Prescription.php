<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_id',
        'practice_id',
        'prescription_number',
        'prescription_date',
        'chief_complaint',
        'diagnosis',
        'medicines',
        'instructions',
        'notes',
        'follow_up_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'prescription_date' => 'date',
            'follow_up_date' => 'date',
            'medicines' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the doctor for this prescription.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient for this prescription.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment for this prescription.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the practice for this prescription.
     */
    public function practice()
    {
        return $this->belongsTo(Practice::class);
    }
}
