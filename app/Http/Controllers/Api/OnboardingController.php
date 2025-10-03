<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Practice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class OnboardingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/onboarding/quick-register",
     *     summary="Quick doctor registration",
     *     description="Step 1: Quick registration with minimal fields for doctors",
     *     tags={"Onboarding"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone","specialization"},
     *             @OA\Property(property="name", type="string", example="Dr. John Doe", description="Full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *             @OA\Property(property="phone", type="string", example="+91-9876543210", description="Phone number with country code"),
     *             @OA\Property(property="specialization", type="string", enum={"Allopathy","Homeopathy","Ayurveda"}, example="Allopathy", description="Medical specialization"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Password (optional for social login)"),
     *             @OA\Property(property="provider", type="string", enum={"google","facebook","email"}, example="email", description="Registration method"),
     *             @OA\Property(property="provider_id", type="string", example="123456789", description="Social provider ID (if applicable)"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg", description="Profile photo URL (if from social login)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Dr. John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="doctor"),
     *                     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                     @OA\Property(property="provider", type="string", example="google")
     *                 ),
     *                 @OA\Property(
     *                     property="doctor",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="specialization", type="string", example="Allopathy"),
     *                     @OA\Property(property="phone", type="string", example="+91-9876543210"),
     *                     @OA\Property(property="onboarding_step", type="integer", example=1),
     *                     @OA\Property(property="onboarding_completed", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation errors"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Registration failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Registration failed"),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function quickRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'specialization' => 'required|in:Allopathy,Homeopathy,Ayurveda',
            'password' => 'nullable|string|min:8',
            'provider' => 'required|in:google,facebook,email',
            'provider_id' => 'nullable|string',
            'avatar' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Handle password for email registration
            $password = null;
            if ($request->provider === 'email' && $request->password) {
                $password = Hash::make($request->password);
            } elseif ($request->provider === 'email') {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is required for email registration'
                ], 422);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $password ?: Hash::make(Str::random(16)), // Random password for social users
                'user_type' => 'doctor',
                'is_active' => true,
                'avatar' => $request->avatar,
                'provider' => $request->provider,
                'provider_id' => $request->provider_id,
                'email_verified_at' => now(), // Auto-verify for quick registration
            ]);

            // Create or find practice based on specialization
            $practice = Practice::firstOrCreate(
                ['name' => $request->specialization . ' Practice', 'type' => $request->specialization],
                [
                    'slug' => Str::slug($request->specialization . ' Practice'),
                    'is_active' => true,
                ]
            );

            // Create doctor profile
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'practice_id' => $practice->id,
                'first_name' => explode(' ', $request->name)[0],
                'last_name' => substr($request->name, strpos($request->name, ' ') + 1) ?: '',
                'specialization' => $request->specialization,
                'phone' => $request->phone,
                'is_available' => true,
                'onboarding_step' => 1,
                'onboarding_completed' => false,
            ]);

            // Assign doctor role
            $user->assignRole('doctor');

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'user_type' => $user->user_type,
                        'avatar' => $user->avatar,
                        'provider' => $user->provider,
                    ],
                    'doctor' => [
                        'id' => $doctor->id,
                        'specialization' => $doctor->specialization,
                        'phone' => $doctor->phone,
                        'onboarding_step' => $doctor->onboarding_step,
                        'onboarding_completed' => $doctor->onboarding_completed,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/onboarding/professional-info",
     *     summary="Update professional information",
     *     description="Step 2.1: Update doctor's professional information",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="qualification", type="string", example="MBBS, MD", description="Medical qualifications"),
     *             @OA\Property(property="registration_number", type="string", example="ABC123456", description="Medical registration number"),
     *             @OA\Property(property="experience_years", type="integer", example=5, description="Years of experience"),
     *             @OA\Property(property="title", type="string", example="Dr.", description="Professional title")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Professional info updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Professional information updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="onboarding_step", type="integer", example=2),
     *                 @OA\Property(property="completion_percentage", type="integer", example=40)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation errors"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function updateProfessionalInfo(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'qualification' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'title' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $doctor->update($request->only([
                'qualification', 'registration_number', 'experience_years', 'title'
            ]));

            // Move to next step
            $doctor->update(['onboarding_step' => 2]);

            $completionPercentage = $this->calculateCompletionPercentage($doctor);

            return response()->json([
                'success' => true,
                'message' => 'Professional information updated successfully',
                'data' => [
                    'onboarding_step' => $doctor->onboarding_step,
                    'completion_percentage' => $completionPercentage,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update professional information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/onboarding/clinic-info",
     *     summary="Update clinic information",
     *     description="Step 2.2: Update doctor's clinic/hospital information",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="clinic_name", type="string", example="City Medical Center", description="Clinic/Hospital name"),
     *             @OA\Property(property="clinic_address", type="string", example="123 Main Street", description="Clinic address"),
     *             @OA\Property(property="clinic_city", type="string", example="Mumbai", description="City"),
     *             @OA\Property(property="clinic_state", type="string", example="Maharashtra", description="State"),
     *             @OA\Property(property="clinic_pincode", type="string", example="400001", description="Pincode"),
     *             @OA\Property(property="clinic_phone", type="string", example="+91-22-12345678", description="Clinic phone number"),
     *             @OA\Property(property="consultation_fees", type="object", example={"general": 500, "followup": 300}, description="Consultation fees"),
     *             @OA\Property(property="working_hours", type="object", example={"monday": {"start": "09:00", "end": "17:00", "available": true}}, description="Working hours")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clinic info updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Clinic information updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="onboarding_step", type="integer", example=3),
     *                 @OA\Property(property="completion_percentage", type="integer", example=70)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation errors"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function updateClinicInfo(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'clinic_name' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string|max:500',
            'clinic_city' => 'nullable|string|max:100',
            'clinic_state' => 'nullable|string|max:100',
            'clinic_pincode' => 'nullable|string|max:10',
            'clinic_phone' => 'nullable|string|max:20',
            'consultation_fees' => 'nullable|array',
            'working_hours' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $doctor->update($request->only([
                'clinic_name', 'clinic_address', 'clinic_city', 'clinic_state', 
                'clinic_pincode', 'clinic_phone', 'consultation_fees', 'working_hours'
            ]));

            // Move to next step
            $doctor->update(['onboarding_step' => 3]);

            $completionPercentage = $this->calculateCompletionPercentage($doctor);

            return response()->json([
                'success' => true,
                'message' => 'Clinic information updated successfully',
                'data' => [
                    'onboarding_step' => $doctor->onboarding_step,
                    'completion_percentage' => $completionPercentage,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update clinic information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate onboarding completion percentage
     */
    private function calculateCompletionPercentage(Doctor $doctor): int
    {
        $totalFields = 10; // Total number of important fields
        $completedFields = 0;

        // Basic info (always completed after registration)
        $completedFields += 4; // name, email, phone, specialization

        // Professional info
        if ($doctor->qualification) $completedFields++;
        if ($doctor->registration_number) $completedFields++;
        if ($doctor->experience_years !== null) $completedFields++;

        // Clinic info
        if ($doctor->clinic_name) $completedFields++;
        if ($doctor->clinic_address) $completedFields++;

        // Documents
        if ($doctor->profile_photo) $completedFields++;

        return min(100, round(($completedFields / $totalFields) * 100));
    }

    /**
     * Get next steps for onboarding
     */
    private function getNextSteps(Doctor $doctor): array
    {
        $nextSteps = [];

        if (!$doctor->qualification) {
            $nextSteps[] = 'Add your medical qualification';
        }
        if (!$doctor->registration_number) {
            $nextSteps[] = 'Add your registration number';
        }
        if (!$doctor->experience_years) {
            $nextSteps[] = 'Add years of experience';
        }
        if (!$doctor->clinic_name) {
            $nextSteps[] = 'Add your clinic/hospital name';
        }
        if (!$doctor->clinic_address) {
            $nextSteps[] = 'Add clinic address';
        }
        if (!$doctor->profile_photo) {
            $nextSteps[] = 'Upload profile photo';
        }

        return $nextSteps;
    }

    /**
     * @OA\Post(
     *     path="/onboarding/upload-documents",
     *     summary="Upload documents",
     *     description="Step 2.3: Upload profile photo and documents (optional)",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="profile_photo", type="string", format="binary", description="Profile photo file"),
     *                 @OA\Property(property="documents", type="array", @OA\Items(type="string", format="binary"), description="Certificate/License files")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Documents uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="onboarding_step", type="integer", example=4),
     *                 @OA\Property(property="completion_percentage", type="integer", example=100),
     *                 @OA\Property(property="profile_photo_url", type="string", example="https://example.com/storage/profile_photo.jpg"),
     *                 @OA\Property(property="document_urls", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation errors"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function uploadDocuments(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents' => 'nullable|array|max:5',
            'documents.*' => 'file|mimes:pdf,jpeg,png,jpg|max:5120', // 5MB max per file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profilePhotoUrl = null;
            $documentUrls = [];

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                $profilePhoto = $request->file('profile_photo');
                $filename = 'profile_' . $doctor->id . '_' . time() . '.' . $profilePhoto->getClientOriginalExtension();
                $path = $profilePhoto->storeAs('public/doctors/profiles', $filename);
                $profilePhotoUrl = Storage::url($path);
                
                $doctor->update(['profile_photo' => $profilePhotoUrl]);
            }

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $document) {
                    $filename = 'document_' . $doctor->id . '_' . $index . '_' . time() . '.' . $document->getClientOriginalExtension();
                    $path = $document->storeAs('public/doctors/documents', $filename);
                    $documentUrls[] = Storage::url($path);
                }
                
                // Store document URLs in the existing documents array
                $existingDocuments = $doctor->documents ?? [];
                $doctor->update(['documents' => array_merge($existingDocuments, $documentUrls)]);
            }

            // Move to completion step
            $doctor->update(['onboarding_step' => 4]);

            $completionPercentage = $this->calculateCompletionPercentage($doctor);

            return response()->json([
                'success' => true,
                'message' => 'Documents uploaded successfully',
                'data' => [
                    'onboarding_step' => $doctor->onboarding_step,
                    'completion_percentage' => $completionPercentage,
                    'profile_photo_url' => $profilePhotoUrl,
                    'document_urls' => $documentUrls,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/onboarding/complete",
     *     summary="Complete onboarding",
     *     description="Mark onboarding as completed",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Onboarding completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Onboarding completed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="onboarding_completed", type="boolean", example=true),
     *                 @OA\Property(property="completion_percentage", type="integer", example=100)
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
    public function completeOnboarding(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

        try {
            $doctor->update([
                'onboarding_completed' => true,
                'onboarding_step' => 4
            ]);

            $completionPercentage = $this->calculateCompletionPercentage($doctor);

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completed successfully',
                'data' => [
                    'onboarding_completed' => $doctor->onboarding_completed,
                    'completion_percentage' => $completionPercentage,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete onboarding',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/onboarding/status",
     *     summary="Get onboarding status",
     *     description="Get current onboarding status and completion percentage",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Onboarding status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="onboarding_step", type="integer", example=2),
     *                 @OA\Property(property="onboarding_completed", type="boolean", example=false),
     *                 @OA\Property(property="completion_percentage", type="integer", example=40),
     *                 @OA\Property(property="next_steps", type="array", @OA\Items(type="string"))
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
    public function getOnboardingStatus(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

        $completionPercentage = $this->calculateCompletionPercentage($doctor);
        $nextSteps = $this->getNextSteps($doctor);

        return response()->json([
            'success' => true,
            'data' => [
                'onboarding_step' => $doctor->onboarding_step,
                'onboarding_completed' => $doctor->onboarding_completed,
                'completion_percentage' => $completionPercentage,
                'next_steps' => $nextSteps,
            ]
        ]);
    }
}