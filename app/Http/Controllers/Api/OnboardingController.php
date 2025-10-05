<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Practice;
use App\Services\AI\SpecializationDetectionService;
use App\Services\AI\OCRService;
use App\Services\AI\ProfileCompletenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class OnboardingController extends Controller
{
    private $specializationDetectionService;
    private $ocrService;
    private $profileCompletenessService;

    public function __construct(
        SpecializationDetectionService $specializationDetectionService,
        OCRService $ocrService,
        ProfileCompletenessService $profileCompletenessService
    ) {
        $this->specializationDetectionService = $specializationDetectionService;
        $this->ocrService = $ocrService;
        $this->profileCompletenessService = $profileCompletenessService;
    }
    /**
     * @OA\Post(
     *     path="/auth/onboarding/quick-register",
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
     *                 @OA\Property(property="completion_percentage", type="integer", example=40),
     *                 @OA\Property(property="detected_specialization", type="string", example="Allopathy", description="Auto-detected specialization from qualification")
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
            $updateData = $request->only([
                'qualification', 'registration_number', 'experience_years', 'title'
            ]);

            // Auto-detect specialization if qualification is provided
            if (!empty($updateData['qualification'])) {
                $specializationResult = $this->specializationDetectionService->detectSpecialization($updateData['qualification']);
                
                if ($specializationResult['confidence'] >= 0.7) {
                    $updateData['specialization'] = $specializationResult['specialization'];
                }
            }

            $doctor->update($updateData);

            // Move to next step
            $doctor->update(['onboarding_step' => 2]);

            $completionPercentage = $this->calculateCompletionPercentage($doctor);

            return response()->json([
                'success' => true,
                'message' => 'Professional information updated successfully',
                'data' => [
                    'onboarding_step' => $doctor->onboarding_step,
                    'completion_percentage' => $completionPercentage,
                    'detected_specialization' => $updateData['specialization'] ?? null,
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
     *                 @OA\Property(property="documents", type="array", @OA\Items(type="string", format="binary"), description="Certificate/License files"),
     *                 @OA\Property(property="document_types", type="array", @OA\Items(type="string", enum={"certificate", "license", "id_card", "degree"}), description="Types of documents (optional, defaults to certificate)")
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
     *                 @OA\Property(property="document_urls", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="extracted_data", type="array", @OA\Items(
     *                     @OA\Property(property="document_type", type="string", example="certificate"),
     *                     @OA\Property(property="structured_data", type="object",
     *                         @OA\Property(property="doctor_name", type="string", example="Dr. John Doe"),
     *                         @OA\Property(property="registration_number", type="string", example="ABC123456"),
     *                         @OA\Property(property="qualification", type="string", example="MBBS, MD")
     *                     ),
     *                     @OA\Property(property="confidence", type="number", example=0.92),
     *                     @OA\Property(property="validation", type="object",
     *                         @OA\Property(property="is_valid", type="boolean", example=true),
     *                         @OA\Property(property="confidence_score", type="number", example=0.92)
     *                     )
     *                 )),
     *                 @OA\Property(property="ai_processing_enabled", type="boolean", example=true)
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
            $extractedData = [];

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                $profilePhoto = $request->file('profile_photo');
                $filename = 'profile_' . $doctor->id . '_' . time() . '.' . $profilePhoto->getClientOriginalExtension();
                $path = $profilePhoto->storeAs('public/doctors/profiles', $filename);
                $profilePhotoUrl = Storage::url($path);
                
                $doctor->update(['profile_photo' => $profilePhotoUrl]);
            }

            // Handle document uploads with AI processing
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $document) {
                    $filename = 'document_' . $doctor->id . '_' . $index . '_' . time() . '.' . $document->getClientOriginalExtension();
                    $path = $document->storeAs('public/doctors/documents', $filename);
                    $documentUrl = Storage::url($path);
                    $documentUrls[] = $documentUrl;

                    // Process document with AI if it's a certificate or license
                    $documentType = $request->input("document_types.{$index}", 'certificate');
                    if (in_array($documentType, ['certificate', 'license', 'degree'])) {
                        try {
                            $extractionResult = $this->ocrService->extractDocumentData($path, $documentType);
                            
                            if ($extractionResult['success'] && !empty($extractionResult['structured_data'])) {
                                $extractedData[] = [
                                    'document_type' => $documentType,
                                    'structured_data' => $extractionResult['structured_data'],
                                    'confidence' => $extractionResult['confidence'],
                                    'validation' => $this->ocrService->validateExtractedData($extractionResult['structured_data'])
                                ];

                                // Auto-update doctor profile with extracted data if confidence is high
                                if ($extractionResult['confidence'] >= 0.8) {
                                    $this->updateDoctorFromExtractedData($doctor, $extractionResult['structured_data'], $documentType);
                                }
                            }
                        } catch (\Exception $e) {
                            // Log error but continue processing
                            \Log::warning('Document processing failed', [
                                'doctor_id' => $doctor->id,
                                'document_index' => $index,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
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
                    'extracted_data' => $extractedData,
                    'ai_processing_enabled' => true
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

        // Get AI-powered profile completeness analysis
        $profileAnalysis = $this->profileCompletenessService->analyzeProfileCompleteness($doctor);
        $aiSuggestions = $profileAnalysis['ai_suggestions'] ?? [];
        $priorityActions = $profileAnalysis['priority_actions'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'onboarding_step' => $doctor->onboarding_step,
                'onboarding_completed' => $doctor->onboarding_completed,
                'completion_percentage' => $completionPercentage,
                'next_steps' => $nextSteps,
                'ai_analysis' => [
                    'completion_percentage' => $profileAnalysis['completion_percentage'] ?? $completionPercentage,
                    'priority_actions' => array_slice($priorityActions, 0, 3), // Top 3 actions
                    'suggestions' => $aiSuggestions['priority_actions'] ?? [],
                    'impact_score' => $profileAnalysis['impact_score'] ?? 0,
                    'estimated_time_to_complete' => $profileAnalysis['estimated_time_to_complete'] ?? '30 minutes'
                ]
            ]
        ]);
    }

    /**
     * Update doctor profile from extracted document data
     */
    private function updateDoctorFromExtractedData(Doctor $doctor, array $structuredData, string $documentType): void
    {
        $updateData = [];

        switch ($documentType) {
            case 'certificate':
            case 'license':
                if (!empty($structuredData['qualification']) && empty($doctor->qualification)) {
                    $updateData['qualification'] = $structuredData['qualification'];
                }
                if (!empty($structuredData['registration_number']) && empty($doctor->registration_number)) {
                    $updateData['registration_number'] = $structuredData['registration_number'];
                }
                if (!empty($structuredData['doctor_name'])) {
                    $nameParts = $this->parseName($structuredData['doctor_name']);
                    if (!empty($nameParts['first_name']) && empty($doctor->first_name)) {
                        $updateData['first_name'] = $nameParts['first_name'];
                    }
                    if (!empty($nameParts['last_name']) && empty($doctor->last_name)) {
                        $updateData['last_name'] = $nameParts['last_name'];
                    }
                    if (!empty($nameParts['title']) && empty($doctor->title)) {
                        $updateData['title'] = $nameParts['title'];
                    }
                }
                break;

            case 'degree':
                if (!empty($structuredData['degree']) && empty($doctor->qualification)) {
                    $updateData['qualification'] = $structuredData['degree'];
                }
                if (!empty($structuredData['student_name'])) {
                    $nameParts = $this->parseName($structuredData['student_name']);
                    if (!empty($nameParts['first_name']) && empty($doctor->first_name)) {
                        $updateData['first_name'] = $nameParts['first_name'];
                    }
                    if (!empty($nameParts['last_name']) && empty($doctor->last_name)) {
                        $updateData['last_name'] = $nameParts['last_name'];
                    }
                }
                break;
        }

        // Auto-detect specialization if qualification is updated
        if (!empty($updateData['qualification'])) {
            $specializationResult = $this->specializationDetectionService->detectSpecialization($updateData['qualification']);
            if ($specializationResult['confidence'] >= 0.7 && empty($doctor->specialization)) {
                $updateData['specialization'] = $specializationResult['specialization'];
            }
        }

        // Update doctor profile if there's data to update
        if (!empty($updateData)) {
            $doctor->update($updateData);
        }
    }

    /**
     * Parse name into components
     */
    private function parseName(string $name): array
    {
        $result = [
            'title' => '',
            'first_name' => '',
            'last_name' => ''
        ];

        // Remove extra whitespace
        $name = trim($name);

        // Extract title
        $titles = ['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'];
        foreach ($titles as $title) {
            if (stripos($name, $title) === 0) {
                $result['title'] = $title;
                $name = trim(substr($name, strlen($title)));
                break;
            }
        }

        // Split name into parts
        $parts = explode(' ', $name);
        if (count($parts) >= 1) {
            $result['first_name'] = $parts[0];
        }
        if (count($parts) > 1) {
            $result['last_name'] = implode(' ', array_slice($parts, 1));
        }

        return $result;
    }
}