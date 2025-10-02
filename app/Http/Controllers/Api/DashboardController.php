<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dashboard/stats",
     *     summary="Get dashboard statistics",
     *     description="Retrieve comprehensive statistics for the doctor's dashboard including patient counts, appointment metrics, and practice analytics",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_patients", type="integer", example=150, description="Total number of patients"),
     *                 @OA\Property(property="total_appointments", type="integer", example=450, description="Total number of appointments"),
     *                 @OA\Property(property="today_appointments", type="integer", example=8, description="Appointments scheduled for today"),
     *                 @OA\Property(property="upcoming_appointments", type="integer", example=25, description="Future scheduled appointments"),
     *                 @OA\Property(property="completed_appointments", type="integer", example=380, description="Completed appointments"),
     *                 @OA\Property(property="total_prescriptions", type="integer", example=320, description="Total prescriptions issued"),
     *                 @OA\Property(property="total_medical_records", type="integer", example=450, description="Total medical records created"),
     *                 @OA\Property(property="pending_appointments", type="integer", example=12, description="Appointments pending confirmation"),
     *                 @OA\Property(property="cancelled_appointments", type="integer", example=15, description="Cancelled appointments"),
     *                 @OA\Property(property="monthly_revenue", type="number", format="decimal", example=125000.00, description="Monthly revenue"),
     *                 @OA\Property(property="average_consultation_fee", type="number", format="decimal", example=500.00, description="Average consultation fee")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doctor profile not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     )
     * )
     */
    public function stats(Request $request)
    {
        $doctor = $request->user()->doctor;
        
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

        $practice = $doctor->practice;

        $stats = [
            'total_patients' => $practice->patients()->count(),
            'total_appointments' => $doctor->appointments()->count(),
            'today_appointments' => $doctor->appointments()->whereDate('appointment_date', today())->count(),
            'upcoming_appointments' => $doctor->appointments()->where('status', 'scheduled')->whereDate('appointment_date', '>=', today())->count(),
            'completed_appointments' => $doctor->appointments()->where('status', 'completed')->count(),
            'total_prescriptions' => $doctor->prescriptions()->count(),
            'total_medical_records' => $doctor->medicalRecords()->count(),
            'pending_appointments' => $doctor->appointments()->where('status', 'scheduled')->count(),
            'cancelled_appointments' => $doctor->appointments()->where('status', 'cancelled')->count(),
            'monthly_revenue' => $doctor->appointments()->where('status', 'completed')->whereMonth('appointment_date', now()->month)->sum('fee') ?? 0,
            'average_consultation_fee' => $doctor->appointments()->where('status', 'completed')->avg('fee') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
