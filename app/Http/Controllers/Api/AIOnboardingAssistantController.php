<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\OnboardingAssistantService;
use App\Services\AI\LanguageSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class AIOnboardingAssistantController extends Controller
{
    private $onboardingAssistantService;
    private $languageSupportService;

    public function __construct(
        OnboardingAssistantService $onboardingAssistantService,
        LanguageSupportService $languageSupportService
    ) {
        $this->onboardingAssistantService = $onboardingAssistantService;
        $this->languageSupportService = $languageSupportService;
    }

    /**
     * @OA\Post(
     *     path="/ai/onboarding/chat",
     *     summary="Chat with AI onboarding assistant",
     *     description="Have a conversational interaction with the AI assistant to complete onboarding",
     *     tags={"AI Onboarding Assistant"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Hi, I'm Dr. John Doe", description="User's message to the AI assistant"),
     *             @OA\Property(property="context", type="object", description="Additional context for the conversation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="AI response generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="response", type="string", example="Hello Dr. Doe! Nice to meet you. I'm here to help you complete your registration."),
     *                 @OA\Property(property="form_data", type="object", description="Extracted form data from the conversation"),
     *                 @OA\Property(property="next_action", type="object",
     *                     @OA\Property(property="type", type="string", example="update_profile"),
     *                     @OA\Property(property="step", type="integer", example=1),
     *                     @OA\Property(property="message", type="string", example="Updating profile with extracted information")
     *                 ),
     *                 @OA\Property(property="current_step", type="integer", example=1),
     *                 @OA\Property(property="completion_percentage", type="integer", example=25),
     *                 @OA\Property(property="suggestions", type="array", @OA\Items(type="string"), example={"Tell me about your medical qualification", "What is your clinic name?"}),
     *                 @OA\Property(property="requires_confirmation", type="boolean", example=false)
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
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'context' => 'nullable|array',
            'language' => 'nullable|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $message = $request->message;
            $context = $request->context ?? [];
            $language = $request->language ?? $this->languageSupportService->detectUserLanguage($request);

            $result = $this->onboardingAssistantService->processConversation($message, $doctor, $context, $language);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['response'],
                    'error' => $result['error'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/onboarding/suggestions",
     *     summary="Get conversation suggestions",
     *     description="Get suggested conversation starters based on current onboarding step",
     *     tags={"AI Onboarding Assistant"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="suggestions", type="array", @OA\Items(type="string"), example={"Hi! I'm Dr. John Doe", "I have MBBS and MD degrees"}),
     *                 @OA\Property(property="current_step", type="integer", example=1),
     *                 @OA\Property(property="step_title", type="string", example="Basic Information")
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
    public function getSuggestions(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $language = $request->query('language') ?? $this->languageSupportService->detectUserLanguage($request);
            $suggestions = $this->onboardingAssistantService->getConversationSuggestions($doctor, $language);
            $currentStep = $doctor->onboarding_step ?? 1;

            $stepTitles = [
                1 => 'Basic Information',
                2 => 'Professional Details',
                3 => 'Clinic Information',
                4 => 'Documents & Completion'
            ];

            // Translate step titles if needed
            if ($language !== 'en') {
                foreach ($stepTitles as $step => &$title) {
                    $translationResult = $this->languageSupportService->translate($title, $language);
                    if ($translationResult['success']) {
                        $title = $translationResult['text'];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => $suggestions,
                    'current_step' => $currentStep,
                    'step_title' => $stepTitles[$currentStep] ?? 'Unknown Step'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/onboarding/progress",
     *     summary="Get onboarding progress summary",
     *     description="Get detailed progress summary of the onboarding process",
     *     tags={"AI Onboarding Assistant"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Progress summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_step", type="integer", example=2),
     *                 @OA\Property(property="completion_percentage", type="integer", example=50),
     *                 @OA\Property(property="steps", type="object",
     *                     @OA\Property(property="1", type="object",
     *                         @OA\Property(property="title", type="string", example="Basic Information"),
     *                         @OA\Property(property="description", type="string", example="Tell us about yourself"),
     *                         @OA\Property(property="completed", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="next_step", type="integer", example=3),
     *                 @OA\Property(property="is_complete", type="boolean", example=false)
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
    public function getProgress(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $language = $request->query('language') ?? $this->languageSupportService->detectUserLanguage($request);
            $progress = $this->onboardingAssistantService->getProgressSummary($doctor, $language);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/onboarding/update-profile",
     *     summary="Update profile from AI conversation",
     *     description="Update doctor profile with data extracted from AI conversation",
     *     tags={"AI Onboarding Assistant"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"form_data"},
     *             @OA\Property(property="form_data", type="object", description="Form data extracted from conversation"),
     *             @OA\Property(property="confirm", type="boolean", example=true, description="Whether to confirm the update")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="updated_fields", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="completion_percentage", type="integer", example=75),
     *                 @OA\Property(property="next_step", type="integer", example=3)
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
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_data' => 'required|array',
            'confirm' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $formData = $request->form_data;
            $confirm = $request->boolean('confirm', true);

            if (!$confirm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Update not confirmed'
                ], 400);
            }

            $updatedFields = [];
            $updateData = [];

            // Update doctor profile
            foreach ($formData as $field => $value) {
                if (in_array($field, ['first_name', 'last_name', 'title', 'qualification', 'registration_number', 'experience_years', 'clinic_name', 'clinic_address', 'clinic_city', 'clinic_state', 'clinic_pincode', 'specialization'])) {
                    if (!empty($value) && $doctor->$field !== $value) {
                        $updateData[$field] = $value;
                        $updatedFields[] = $field;
                    }
                }
            }

            // Update user profile
            $userUpdateData = [];
            foreach ($formData as $field => $value) {
                if (in_array($field, ['name', 'email', 'phone'])) {
                    if (!empty($value) && $user->$field !== $value) {
                        $userUpdateData[$field] = $value;
                        $updatedFields[] = $field;
                    }
                }
            }

            if (!empty($updateData)) {
                $doctor->update($updateData);
            }

            if (!empty($userUpdateData)) {
                $user->update($userUpdateData);
            }

            // Calculate new completion percentage
            $completionPercentage = $this->onboardingAssistantService->getProgressSummary($doctor)['completion_percentage'];

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'updated_fields' => $updatedFields,
                    'completion_percentage' => $completionPercentage,
                    'next_step' => $doctor->onboarding_step
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/onboarding/start",
     *     summary="Start AI onboarding conversation",
     *     description="Initialize the AI onboarding assistant and get the first message",
     *     tags={"AI Onboarding Assistant"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="AI onboarding started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="welcome_message", type="string", example="Hello! I'm your AI assistant here to help you complete your doctor registration."),
     *                 @OA\Property(property="current_step", type="integer", example=1),
     *                 @OA\Property(property="completion_percentage", type="integer", example=25),
     *                 @OA\Property(property="suggestions", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="progress", type="object")
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
    public function start(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $currentStep = $doctor->onboarding_step ?? 1;
            $completionPercentage = $this->onboardingAssistantService->getProgressSummary($doctor)['completion_percentage'];
            $suggestions = $this->onboardingAssistantService->getConversationSuggestions($doctor);
            $progress = $this->onboardingAssistantService->getProgressSummary($doctor);

            $welcomeMessages = [
                1 => "Hello! I'm your AI assistant here to help you complete your doctor registration. Let's start with some basic information about you.",
                2 => "Great! Now let's gather your professional details. Tell me about your medical qualifications and experience.",
                3 => "Excellent! Now I need to know about your practice. Where do you work and what are your clinic details?",
                4 => "Almost done! Let's complete your registration by uploading your documents and finalizing your profile."
            ];

            $welcomeMessage = $welcomeMessages[$currentStep] ?? "Hello! I'm here to help you complete your registration.";

            return response()->json([
                'success' => true,
                'data' => [
                    'welcome_message' => $welcomeMessage,
                    'current_step' => $currentStep,
                    'completion_percentage' => $completionPercentage,
                    'suggestions' => $suggestions,
                    'progress' => $progress
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start AI onboarding',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
