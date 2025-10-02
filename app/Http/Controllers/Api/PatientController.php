<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class PatientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/patients",
     *     summary="Get all patients",
     *     description="Retrieve a paginated list of all patients for the authenticated doctor's practice",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of patients per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for patient name or ID",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patients retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Patient")),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;
        $practiceId = $doctor->practice_id;

        $query = Patient::where('practice_id', $practiceId)->with('practice');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('patient_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $patients = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);
    }

    /**
     * @OA\Post(
     *     path="/patients",
     *     summary="Create a new patient",
     *     description="Add a new patient to the practice",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","phone","date_of_birth","gender"},
     *             @OA\Property(property="first_name", type="string", example="John", description="Patient's first name"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Patient's last name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Patient's email", nullable=true),
     *             @OA\Property(property="phone", type="string", example="+91-9876543210", description="Patient's phone number"),
     *             @OA\Property(property="alternate_phone", type="string", example="+91-9876543211", description="Alternate phone number", nullable=true),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-15", description="Patient's date of birth"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male", description="Patient's gender"),
     *             @OA\Property(property="blood_group", type="string", example="A+", description="Blood group", nullable=true),
     *             @OA\Property(property="address", type="string", example="123 Main St, City", description="Address", nullable=true),
     *             @OA\Property(property="city", type="string", example="Mumbai", description="City", nullable=true),
     *             @OA\Property(property="state", type="string", example="Maharashtra", description="State", nullable=true),
     *             @OA\Property(property="pincode", type="string", example="400001", description="Pincode", nullable=true),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe", description="Emergency contact name", nullable=true),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+91-9876543212", description="Emergency contact phone", nullable=true),
     *             @OA\Property(property="medical_history", type="array", @OA\Items(type="string"), example={"Diabetes", "Hypertension"}, description="Medical history", nullable=true),
     *             @OA\Property(property="allergies", type="array", @OA\Items(type="string"), example={"Penicillin", "Dust"}, description="Known allergies", nullable=true),
     *             @OA\Property(property="medications", type="array", @OA\Items(type="string"), example={"Metformin", "Lisinopril"}, description="Current medications", nullable=true),
     *             @OA\Property(property="notes", type="string", example="Patient notes", description="Additional notes", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Patient created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Patient created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Patient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'medical_history' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medications' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $request->user()->doctor;
        $practiceId = $doctor->practice_id;

        // Generate unique patient ID
        $patientId = 'PAT-' . strtoupper(Str::random(8));

        $patient = Patient::create([
            'patient_id' => $patientId,
            'practice_id' => $practiceId,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'blood_group' => $request->blood_group,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'medical_history' => $request->medical_history,
            'allergies' => $request->allergies,
            'medications' => $request->medications,
            'notes' => $request->notes,
        ]);

        $patient->load('practice');

        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully',
            'data' => $patient
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/patients/{id}",
     *     summary="Get patient details",
     *     description="Retrieve detailed information about a specific patient",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Patient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $practiceId = $doctor->practice_id;

        $patient = Patient::where('practice_id', $practiceId)
                         ->with(['practice', 'appointments.doctor', 'prescriptions.doctor', 'medicalRecords.doctor'])
                         ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $patient
        ]);
    }

    /**
     * @OA\Put(
     *     path="/patients/{id}",
     *     summary="Update patient information",
     *     description="Update patient details",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John", description="Patient's first name"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Patient's last name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Patient's email", nullable=true),
     *             @OA\Property(property="phone", type="string", example="+91-9876543210", description="Patient's phone number"),
     *             @OA\Property(property="alternate_phone", type="string", example="+91-9876543211", description="Alternate phone number", nullable=true),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-15", description="Patient's date of birth"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male", description="Patient's gender"),
     *             @OA\Property(property="blood_group", type="string", example="A+", description="Blood group", nullable=true),
     *             @OA\Property(property="address", type="string", example="123 Main St, City", description="Address", nullable=true),
     *             @OA\Property(property="city", type="string", example="Mumbai", description="City", nullable=true),
     *             @OA\Property(property="state", type="string", example="Maharashtra", description="State", nullable=true),
     *             @OA\Property(property="pincode", type="string", example="400001", description="Pincode", nullable=true),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe", description="Emergency contact name", nullable=true),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+91-9876543212", description="Emergency contact phone", nullable=true),
     *             @OA\Property(property="medical_history", type="array", @OA\Items(type="string"), example={"Diabetes", "Hypertension"}, description="Medical history", nullable=true),
     *             @OA\Property(property="allergies", type="array", @OA\Items(type="string"), example={"Penicillin", "Dust"}, description="Known allergies", nullable=true),
     *             @OA\Property(property="medications", type="array", @OA\Items(type="string"), example={"Metformin", "Lisinopril"}, description="Current medications", nullable=true),
     *             @OA\Property(property="notes", type="string", example="Patient notes", description="Additional notes", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Patient updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Patient")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'date_of_birth' => 'sometimes|required|date',
            'gender' => 'sometimes|required|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'medical_history' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medications' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $request->user()->doctor;
        $practiceId = $doctor->practice_id;

        $patient = Patient::where('practice_id', $practiceId)->findOrFail($id);
        $patient->update($request->only([
            'first_name', 'last_name', 'email', 'phone', 'alternate_phone',
            'date_of_birth', 'gender', 'blood_group', 'address', 'city',
            'state', 'pincode', 'emergency_contact_name', 'emergency_contact_phone',
            'medical_history', 'allergies', 'medications', 'notes'
        ]));

        $patient->load('practice');

        return response()->json([
            'success' => true,
            'message' => 'Patient updated successfully',
            'data' => $patient
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/patients/{id}",
     *     summary="Delete patient",
     *     description="Soft delete a patient (mark as inactive)",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Patient deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $practiceId = $doctor->practice_id;

        $patient = Patient::where('practice_id', $practiceId)->findOrFail($id);
        $patient->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Patient deleted successfully'
        ]);
    }
}
