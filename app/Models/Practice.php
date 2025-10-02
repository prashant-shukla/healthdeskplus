<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Practice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'license_number',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'phone',
        'email',
        'website',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the doctors associated with this practice.
     */
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    /**
     * Get the patients associated with this practice.
     */
    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Get the appointments associated with this practice.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the prescriptions associated with this practice.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the medical records associated with this practice.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
