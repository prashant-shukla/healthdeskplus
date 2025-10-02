<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = Doctor::with(['user', 'practice'])->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $doctors
        ]);
    }

    public function show(Request $request, $id)
    {
        $doctor = Doctor::with(['user', 'practice'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $doctor
        ]);
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);
        
        $doctor->update($request->only([
            'first_name', 'last_name', 'title', 'specialization', 'qualification',
            'registration_number', 'phone', 'date_of_birth', 'gender', 'bio',
            'consultation_fees', 'working_hours', 'experience_years', 'is_available'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Doctor profile updated successfully',
            'data' => $doctor
        ]);
    }

    public function appointments(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);
        $appointments = $doctor->appointments()->with('patient')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }

    public function patients(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);
        $patients = $doctor->practice->patients()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);
    }
}