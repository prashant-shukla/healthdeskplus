<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;
        $prescriptions = Prescription::where('doctor_id', $doctor->id)
                                   ->with(['patient', 'appointment'])
                                   ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $prescriptions
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'prescription_date' => 'required|date',
            'medicines' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $request->user()->doctor;
        $prescription = Prescription::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'practice_id' => $doctor->practice_id,
            'prescription_number' => 'PRES-' . uniqid(),
            'prescription_date' => $request->prescription_date,
            'chief_complaint' => $request->chief_complaint,
            'diagnosis' => $request->diagnosis,
            'medicines' => $request->medicines,
            'instructions' => $request->instructions,
            'notes' => $request->notes,
            'follow_up_date' => $request->follow_up_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prescription created successfully',
            'data' => $prescription
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $prescription = Prescription::where('doctor_id', $doctor->id)
                                  ->with(['patient', 'appointment'])
                                  ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $prescription
        ]);
    }

    public function update(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $prescription = Prescription::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $prescription->update($request->only([
            'prescription_date', 'chief_complaint', 'diagnosis', 'medicines',
            'instructions', 'notes', 'follow_up_date'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Prescription updated successfully',
            'data' => $prescription
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $prescription = Prescription::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $prescription->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Prescription deleted successfully'
        ]);
    }
}