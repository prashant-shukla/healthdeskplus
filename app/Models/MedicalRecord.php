<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_id',
        'practice_id',
        'record_number',
        'record_date',
        'type',
        'title',
        'content',
        'vital_signs',
        'attachments',
        'notes',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'vital_signs' => 'array',
            'attachments' => 'array',
            'is_private' => 'boolean',
        ];
    }

    /**
     * Get the doctor for this medical record.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient for this medical record.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment for this medical record.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the practice for this medical record.
     */
    public function practice()
    {
        return $this->belongsTo(Practice::class);
    }
}
