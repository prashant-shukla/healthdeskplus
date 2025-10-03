<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'practice_id',
        'first_name',
        'last_name',
        'title',
        'specialization',
        'qualification',
        'registration_number',
        'phone',
        'date_of_birth',
        'gender',
        'bio',
        'consultation_fees',
        'working_hours',
        'experience_years',
        'is_available',
        'onboarding_step',
        'onboarding_completed',
        'profile_photo',
        'documents',
        'clinic_name',
        'clinic_address',
        'clinic_city',
        'clinic_state',
        'clinic_pincode',
        'clinic_phone',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'consultation_fees' => 'array',
            'working_hours' => 'array',
            'settings' => 'array',
            'is_available' => 'boolean',
            'onboarding_completed' => 'boolean',
            'documents' => 'array',
        ];
    }

    /**
     * Get the user associated with this doctor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the practice associated with this doctor.
     */
    public function practice()
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the appointments for this doctor.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the prescriptions created by this doctor.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the medical records created by this doctor.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Get the full name of the doctor.
     */
    public function getFullNameAttribute()
    {
        return $this->title . ' ' . $this->first_name . ' ' . $this->last_name;
    }
}
