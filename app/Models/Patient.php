<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'practice_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'alternate_phone',
        'date_of_birth',
        'gender',
        'blood_group',
        'address',
        'city',
        'state',
        'pincode',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_history',
        'allergies',
        'medications',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'medical_history' => 'array',
            'allergies' => 'array',
            'medications' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the practice associated with this patient.
     */
    public function practice()
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the appointments for this patient.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the prescriptions for this patient.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the medical records for this patient.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Get the full name of the patient.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
