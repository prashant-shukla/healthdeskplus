<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\LanguageSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class LanguageSupportController extends Controller
{
    private $languageSupportService;

    public function __construct(LanguageSupportService $languageSupportService)
    {
        $this->languageSupportService = $languageSupportService;
    }

    /**
     * @OA\Post(
     *     path="/ai/translate",
     *     summary="Translate text to target language",
     *     description="Translate text using Google Translate API",
     *     tags={"Language Support"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text", "target_language"},
     *             @OA\Property(property="text", type="string", example="Hello, how are you?", description="Text to translate"),
     *             @OA\Property(property="target_language", type="string", example="hi", description="Target language code"),
     *             @OA\Property(property="source_language", type="string", example="en", description="Source language code (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="text", type="string", example="नमस्ते, आप कैसे हैं?"),
     *                 @OA\Property(property="source_language", type="string", example="en"),
     *                 @OA\Property(property="target_language", type="string", example="hi"),
     *                 @OA\Property(property="translated", type="boolean", example=true),
     *                 @OA\Property(property="confidence", type="number", example=0.95)
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
    public function translate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $text = $request->text;
            $targetLanguage = $request->target_language;
            $sourceLanguage = $request->source_language;

            $result = $this->languageSupportService->translate($text, $targetLanguage, $sourceLanguage);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Translation failed'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Translation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/translate-batch",
     *     summary="Translate multiple texts",
     *     description="Translate multiple texts in a single request",
     *     tags={"Language Support"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"texts", "target_language"},
     *             @OA\Property(property="texts", type="object", example={"greeting": "Hello", "farewell": "Goodbye"}, description="Texts to translate"),
     *             @OA\Property(property="target_language", type="string", example="hi", description="Target language code"),
     *             @OA\Property(property="source_language", type="string", example="en", description="Source language code (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Batch translation completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="greeting", type="object",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="text", type="string", example="नमस्ते")
     *                 ),
     *                 @OA\Property(property="farewell", type="object",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="text", type="string", example="अलविदा")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function translateBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'texts' => 'required|array|max:50',
            'texts.*' => 'string|max:1000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $texts = $request->texts;
            $targetLanguage = $request->target_language;
            $sourceLanguage = $request->source_language;

            $results = $this->languageSupportService->translateBatch($texts, $targetLanguage, $sourceLanguage);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch translation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/detect-language",
     *     summary="Detect language of text",
     *     description="Detect the language of input text using Google Translate API",
     *     tags={"Language Support"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", example="नमस्ते, आप कैसे हैं?", description="Text to detect language for")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language detection completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="language", type="string", example="hi"),
     *                 @OA\Property(property="confidence", type="number", example=0.95),
     *                 @OA\Property(property="language_name", type="string", example="Hindi"),
     *                 @OA\Property(property="native_name", type="string", example="हिन्दी")
     *             )
     *         )
     *     )
     * )
     */
    public function detectLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $text = $request->text;

            $result = $this->languageSupportService->detectLanguage($text);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Language detection failed'
                ], 500);
            }

            // Add language names
            $result['language_name'] = $this->languageSupportService->getLanguageName($result['language']);
            $result['native_name'] = $this->languageSupportService->getNativeLanguageName($result['language']);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Language detection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/languages",
     *     summary="Get supported languages",
     *     description="Get list of all supported languages for translation",
     *     tags={"Language Support"},
     *     @OA\Response(
     *         response=200,
     *         description="Supported languages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="en", type="object",
     *                     @OA\Property(property="name", type="string", example="English"),
     *                     @OA\Property(property="native_name", type="string", example="English")
     *                 ),
     *                 @OA\Property(property="hi", type="object",
     *                     @OA\Property(property="name", type="string", example="Hindi"),
     *                     @OA\Property(property="native_name", type="string", example="हिन्दी")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getSupportedLanguages()
    {
        try {
            $languages = $this->languageSupportService->getSupportedLanguages();

            return response()->json([
                'success' => true,
                'data' => $languages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get supported languages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/languages/region/{region}",
     *     summary="Get language preferences for region",
     *     description="Get language preferences for a specific region",
     *     tags={"Language Support"},
     *     @OA\Parameter(
     *         name="region",
     *         in="path",
     *         description="Region code (e.g., IN, US, GB)",
     *         required=true,
     *         @OA\Schema(type="string", example="IN")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language preferences retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="region", type="string", example="IN"),
     *                 @OA\Property(property="languages", type="array", @OA\Items(type="string"), example={"hi", "en", "ta", "te"}),
     *                 @OA\Property(property="primary_language", type="string", example="hi"),
     *                 @OA\Property(property="fallback_language", type="string", example="en")
     *             )
     *         )
     *     )
     * )
     */
    public function getLanguagePreferencesForRegion(Request $request, string $region)
    {
        try {
            $languages = $this->languageSupportService->getLanguagePreferencesForRegion($region);

            return response()->json([
                'success' => true,
                'data' => [
                    'region' => $region,
                    'languages' => $languages,
                    'primary_language' => $languages[0] ?? 'en',
                    'fallback_language' => 'en'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get language preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/medical-terms",
     *     summary="Get medical terms in target language",
     *     description="Get common medical terms translated to target language",
     *     tags={"Language Support"},
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Target language code",
     *         required=true,
     *         @OA\Schema(type="string", example="hi")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medical terms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="language", type="string", example="hi"),
     *                 @OA\Property(property="terms", type="object",
     *                     @OA\Property(property="doctor", type="string", example="डॉक्टर"),
     *                     @OA\Property(property="patient", type="string", example="मरीज"),
     *                     @OA\Property(property="appointment", type="string", example="अपॉइंटमेंट")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getMedicalTerms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $language = $request->language;
            $terms = $this->languageSupportService->getMedicalTerms($language);

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => $language,
                    'terms' => $terms
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get medical terms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/detect-user-language",
     *     summary="Detect user's preferred language",
     *     description="Detect user's preferred language from request headers and parameters",
     *     tags={"Language Support"},
     *     @OA\Response(
     *         response=200,
     *         description="User language detected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="language", type="string", example="hi"),
     *                 @OA\Property(property="language_name", type="string", example="Hindi"),
     *                 @OA\Property(property="native_name", type="string", example="हिन्दी"),
     *                 @OA\Property(property="detection_method", type="string", example="accept_language_header")
     *             )
     *         )
     *     )
     * )
     */
    public function detectUserLanguage(Request $request)
    {
        try {
            $language = $this->languageSupportService->detectUserLanguage($request);
            $detectionMethod = 'default';

            // Determine detection method
            if ($request->header('Accept-Language')) {
                $detectionMethod = 'accept_language_header';
            } elseif ($request->query('lang')) {
                $detectionMethod = 'query_parameter';
            } elseif ($request->user() && isset($request->user()->language_preference)) {
                $detectionMethod = 'user_preference';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => $language,
                    'language_name' => $this->languageSupportService->getLanguageName($language),
                    'native_name' => $this->languageSupportService->getNativeLanguageName($language),
                    'detection_method' => $detectionMethod
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detect user language',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
