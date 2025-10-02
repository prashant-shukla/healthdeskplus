<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Patient permissions
            'view_patients',
            'create_patients',
            'edit_patients',
            'delete_patients',
            
            // Appointment permissions
            'view_appointments',
            'create_appointments',
            'edit_appointments',
            'delete_appointments',
            'confirm_appointments',
            'cancel_appointments',
            
            // Prescription permissions
            'view_prescriptions',
            'create_prescriptions',
            'edit_prescriptions',
            'delete_prescriptions',
            
            // Medical record permissions
            'view_medical_records',
            'create_medical_records',
            'edit_medical_records',
            'delete_medical_records',
            
            // Practice permissions
            'view_practice',
            'edit_practice',
            'manage_practice_settings',
            
            // Doctor permissions
            'view_doctors',
            'edit_own_profile',
            'view_own_appointments',
            'view_own_patients',
            
            // Admin permissions
            'manage_users',
            'manage_roles',
            'view_analytics',
            'manage_system_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $doctorRole = Role::create(['name' => 'doctor']);
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);

        // Assign permissions to roles
        $doctorRole->givePermissionTo([
            'view_patients', 'create_patients', 'edit_patients',
            'view_appointments', 'create_appointments', 'edit_appointments', 'confirm_appointments', 'cancel_appointments',
            'view_prescriptions', 'create_prescriptions', 'edit_prescriptions',
            'view_medical_records', 'create_medical_records', 'edit_medical_records',
            'view_practice', 'edit_practice',
            'edit_own_profile', 'view_own_appointments', 'view_own_patients',
        ]);

        $staffRole->givePermissionTo([
            'view_patients', 'create_patients', 'edit_patients',
            'view_appointments', 'create_appointments', 'edit_appointments',
            'view_prescriptions', 'create_prescriptions',
            'view_medical_records', 'create_medical_records',
            'view_practice',
        ]);

        $adminRole->givePermissionTo(Permission::all());
    }
}
