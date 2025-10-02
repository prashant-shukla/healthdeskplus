<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class AppointmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/appointments",
     *     summary="Get all appointments",
     *     description="Retrieve a paginated list of all appointments for the authenticated doctor",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Appointments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;
        $appointments = Appointment::where('doctor_id', $doctor->id)
                                 ->with(['patient', 'doctor'])
                                 ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'type' => 'required|in:consultation,follow_up,emergency,surgery,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $request->user()->doctor;
        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $request->patient_id,
            'practice_id' => $doctor->practice_id,
            'appointment_number' => 'APT-' . uniqid(),
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'type' => $request->type,
            'status' => 'scheduled',
            'reason' => $request->reason,
            'notes' => $request->notes,
            'fee' => $request->fee,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully',
            'data' => $appointment
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $appointment = Appointment::where('doctor_id', $doctor->id)
                                 ->with(['patient', 'doctor'])
                                 ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $appointment
        ]);
    }

    public function update(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $appointment = Appointment::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $appointment->update($request->only([
            'appointment_date', 'appointment_time', 'type', 'reason', 'notes', 'fee'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'data' => $appointment
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $appointment = Appointment::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully'
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $appointment = Appointment::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment confirmed successfully'
        ]);
    }

    public function complete(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $appointment = Appointment::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $appointment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment completed successfully'
        ]);
    }
}