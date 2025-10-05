<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\GooglePlacesService;
use App\Services\AI\OpenAIService;
use App\Services\AI\OCRService;
use App\Services\AI\SpecializationDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class AIAssistantController extends Controller
{
    private $googlePlacesService;
    private $openAIService;
    private $ocrService;
    private $specializationDetectionService;

    public function __construct(
        GooglePlacesService $googlePlacesService,
        OpenAIService $openAIService,
        SpecializationDetectionService $specializationDetectionService,
        ?OCRService $ocrService = null
    ) {
        $this->googlePlacesService = $googlePlacesService;
        $this->openAIService = $openAIService;
        $this->specializationDetectionService = $specializationDetectionService;
        $this->ocrService = $ocrService;
    }

    /**
     * @OA\Post(
     *     path="/ai/autocomplete/clinic",
     *     summary="Get clinic autocomplete suggestions",
     *     description="Get smart autocomplete suggestions for clinic names and addresses using Google Places API",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"input"},
     *             @OA\Property(property="input", type="string", example="Apollo", description="Input text for autocomplete"),
     *             @OA\Property(property="city", type="string", example="Mumbai", description="City to filter results"),
     *             @OA\Property(property="types", type="array", @OA\Items(type="string"), example={"hospital", "doctor"}, description="Types of places to search")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autocomplete suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="place_id", type="string", example="mock_apollo_mumbai"),
     *                 @OA\Property(property="description", type="string", example="Apollo Hospital, Mumbai"),
     *                 @OA\Property(property="main_text", type="string", example="Apollo Hospital"),
     *                 @OA\Property(property="secondary_text", type="string", example="Mumbai, Maharashtra, India"),
     *                 @OA\Property(property="types", type="array", @OA\Items(type="string"))
     *             ))
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
    public function getClinicAutocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|min:2|max:100',
            'city' => 'nullable|string|max:100',
            'types' => 'nullable|array',
            'types.*' => 'string|in:hospital,doctor,health,pharmacy,establishment'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $input = $request->input;
            $city = $request->city;
            $types = $request->types ?? ['hospital', 'doctor', 'health'];

            // First, try to refine the input using OpenAI
            $refinedInput = $this->openAIService->refineInput($input, 'clinic_name');

            // Get autocomplete suggestions
            if ($city) {
                $suggestions = $this->googlePlacesService->searchMedicalEstablishments($refinedInput, $city);
            } else {
                $suggestions = $this->googlePlacesService->getAutocompleteSuggestions($refinedInput, $types);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'refined_input' => $refinedInput !== $input ? $refinedInput : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get autocomplete suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/autocomplete/address",
     *     summary="Get address autocomplete suggestions",
     *     description="Get smart autocomplete suggestions for addresses using Google Places API",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"input"},
     *             @OA\Property(property="input", type="string", example="123 Main St", description="Input text for autocomplete"),
     *             @OA\Property(property="city", type="string", example="Mumbai", description="City to filter results")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address autocomplete suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="place_id", type="string", example="mock_apollo_mumbai"),
     *                 @OA\Property(property="description", type="string", example="123 Main Street, Mumbai"),
     *                 @OA\Property(property="main_text", type="string", example="123 Main Street"),
     *                 @OA\Property(property="secondary_text", type="string", example="Mumbai, Maharashtra, India")
     *             ))
     *         )
     *     )
     * )
     */
    public function getAddressAutocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|min:2|max:200',
            'city' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $input = $request->input;
            $city = $request->city;

            // Refine the input using OpenAI
            $refinedInput = $this->openAIService->refineInput($input, 'address');

            // Get address suggestions
            $suggestions = $this->googlePlacesService->getAutocompleteSuggestions($refinedInput, ['street_address']);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'refined_input' => $refinedInput !== $input ? $refinedInput : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get address suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/place-details",
     *     summary="Get place details by place ID",
     *     description="Get detailed information about a place using Google Places API",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"place_id"},
     *             @OA\Property(property="place_id", type="string", example="mock_apollo_mumbai", description="Google Places place ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Place details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="Apollo Hospital"),
     *                 @OA\Property(property="formatted_address", type="string", example="123 Main St, Mumbai, Maharashtra, India"),
     *                 @OA\Property(property="clinic_address", type="string", example="123 Main St"),
     *                 @OA\Property(property="clinic_city", type="string", example="Mumbai"),
     *                 @OA\Property(property="clinic_state", type="string", example="Maharashtra"),
     *                 @OA\Property(property="clinic_pincode", type="string", example="400001"),
     *                 @OA\Property(property="phone", type="string", example="+91-22-12345678"),
     *                 @OA\Property(property="website", type="string", example="https://apollohospitals.com"),
     *                 @OA\Property(property="coordinates", type="object",
     *                     @OA\Property(property="lat", type="number", example=19.0760),
     *                     @OA\Property(property="lng", type="number", example=72.8777)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getPlaceDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $placeId = $request->place_id;
            $details = $this->googlePlacesService->getPlaceDetails($placeId);

            if (!$details) {
                return response()->json([
                    'success' => false,
                    'message' => 'Place details not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get place details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/detect-specialization",
     *     summary="Detect specialization from qualification text",
     *     description="Use enhanced AI and rule-based detection to identify medical specialization from qualification text",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qualification"},
     *             @OA\Property(property="qualification", type="string", example="MBBS, MD", description="Medical qualification text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specialization detected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="specialization", type="string", example="Allopathy"),
     *                 @OA\Property(property="confidence", type="number", example=0.95),
     *                 @OA\Property(property="extracted_qualifications", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="suggested_specialization_text", type="string", example="Allopathy"),
     *                 @OA\Property(property="detection_method", type="string", example="rule_based"),
     *                 @OA\Property(property="specialization_details", type="object",
     *                     @OA\Property(property="name", type="string", example="Allopathy"),
     *                     @OA\Property(property="full_name", type="string", example="Allopathic Medicine"),
     *                     @OA\Property(property="description", type="string", example="Modern Western medicine system"),
     *                     @OA\Property(property="council", type="string", example="Medical Council of India (MCI)"),
     *                     @OA\Property(property="practice_type", type="string", example="allopathy")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function detectSpecialization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qualification' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $qualification = $request->qualification;
            $result = $this->specializationDetectionService->detectSpecialization($qualification);
            
            // Add specialization details
            $result['specialization_details'] = $this->specializationDetectionService->getSpecializationDetails($result['specialization']);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detect specialization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/refine-input",
     *     summary="Refine and correct user input",
     *     description="Use OpenAI to refine and correct messy user input",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"input", "context"},
     *             @OA\Property(property="input", type="string", example="Applo hosp", description="Input text to refine"),
     *             @OA\Property(property="context", type="string", enum={"clinic_name", "address", "qualification", "general"}, example="clinic_name", description="Context for refinement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Input refined successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="original_input", type="string", example="Applo hosp"),
     *                 @OA\Property(property="refined_input", type="string", example="Apollo Hospital"),
     *                 @OA\Property(property="was_changed", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function refineInput(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|max:500',
            'context' => 'required|string|in:clinic_name,address,qualification,general'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $input = $request->input;
            $context = $request->context;
            
            $refinedInput = $this->openAIService->refineInput($input, $context);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_input' => $input,
                    'refined_input' => $refinedInput,
                    'was_changed' => $refinedInput !== $input
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refine input',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/specializations",
     *     summary="Get all supported specializations",
     *     description="Get list of all supported medical specializations with details",
     *     tags={"AI Assistant"},
     *     @OA\Response(
     *         response=200,
     *         description="Specializations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="Allopathy", type="object",
     *                     @OA\Property(property="name", type="string", example="Allopathy"),
     *                     @OA\Property(property="full_name", type="string", example="Allopathic Medicine"),
     *                     @OA\Property(property="description", type="string", example="Modern Western medicine system"),
     *                     @OA\Property(property="common_qualifications", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="council", type="string", example="Medical Council of India (MCI)"),
     *                     @OA\Property(property="practice_type", type="string", example="allopathy")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllSpecializations()
    {
        try {
            $specializations = $this->specializationDetectionService->getAllSpecializations();

            return response()->json([
                'success' => true,
                'data' => $specializations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get specializations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/validate-qualification",
     *     summary="Validate qualification against specialization",
     *     description="Validate if a qualification matches a specific specialization",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qualification", "specialization"},
     *             @OA\Property(property="qualification", type="string", example="MBBS", description="Medical qualification"),
     *             @OA\Property(property="specialization", type="string", example="Allopathy", description="Medical specialization")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Qualification validation completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_valid", type="boolean", example=true),
     *                 @OA\Property(property="matched_qualification", type="string", example="MBBS"),
     *                 @OA\Property(property="suggested_qualifications", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="confidence", type="number", example=0.9)
     *             )
     *         )
     *     )
     * )
     */
    public function validateQualification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qualification' => 'required|string|max:500',
            'specialization' => 'required|string|in:Allopathy,Homeopathy,Ayurveda,Unani,Siddha'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $qualification = $request->qualification;
            $specialization = $request->specialization;
            
            $result = $this->specializationDetectionService->validateQualification($qualification, $specialization);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate qualification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/suggest-qualifications",
     *     summary="Suggest qualifications for specialization",
     *     description="Get suggested qualifications for a specific medical specialization",
     *     tags={"AI Assistant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"specialization"},
     *             @OA\Property(property="specialization", type="string", example="Allopathy", description="Medical specialization")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Qualifications suggested successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="primary", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="secondary", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="diploma", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function suggestQualifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialization' => 'required|string|in:Allopathy,Homeopathy,Ayurveda,Unani,Siddha'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $specialization = $request->specialization;
            $suggestions = $this->specializationDetectionService->suggestQualifications($specialization);

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suggest qualifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
