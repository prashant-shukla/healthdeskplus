<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\OCRService;
use App\Services\AI\OpenAIService;
use App\Services\AI\SpecializationDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class DocumentProcessingController extends Controller
{
    private $ocrService;
    private $openAIService;
    private $specializationDetectionService;

    public function __construct(
        OCRService $ocrService,
        OpenAIService $openAIService,
        SpecializationDetectionService $specializationDetectionService
    ) {
        $this->ocrService = $ocrService;
        $this->openAIService = $openAIService;
        $this->specializationDetectionService = $specializationDetectionService;
    }

    /**
     * @OA\Post(
     *     path="/ai/process-document",
     *     summary="Process uploaded document with OCR and AI extraction",
     *     description="Upload a document and extract structured data using OCR and AI",
     *     tags={"Document Processing"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="document", type="string", format="binary", description="Document file (image or PDF)"),
     *                 @OA\Property(property="document_type", type="string", enum={"certificate", "license", "id_card", "degree"}, example="certificate", description="Type of document"),
     *                 @OA\Property(property="auto_fill_form", type="boolean", example=true, description="Whether to auto-fill form fields")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="document_type", type="string", example="certificate"),
     *                 @OA\Property(property="raw_text", type="string", example="Dr. John Doe MBBS..."),
     *                 @OA\Property(property="structured_data", type="object",
     *                     @OA\Property(property="doctor_name", type="string", example="Dr. John Doe"),
     *                     @OA\Property(property="registration_number", type="string", example="ABC123456"),
     *                     @OA\Property(property="qualification", type="string", example="MBBS, MD"),
     *                     @OA\Property(property="issuing_authority", type="string", example="Medical Council of India")
     *                 ),
     *                 @OA\Property(property="confidence", type="number", example=0.85),
     *                 @OA\Property(property="validation", type="object",
     *                     @OA\Property(property="is_valid", type="boolean", example=true),
     *                     @OA\Property(property="confidence_score", type="number", example=0.85),
     *                     @OA\Property(property="missing_fields", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="warnings", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="suggestions", type="array", @OA\Items(type="string"))
     *                 ),
     *                 @OA\Property(property="form_data", type="object", description="Auto-filled form data")
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
    public function processDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240', // 10MB max
            'document_type' => 'required|string|in:certificate,license,id_card,degree',
            'auto_fill_form' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store the uploaded file
            $file = $request->file('document');
            $filename = 'document_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('temp/documents', $filename, 'public');

            // Process the document
            $documentType = $request->document_type;
            $result = $this->ocrService->extractDocumentData($path, $documentType);

            if (!$result['success']) {
                // Clean up the file
                Storage::disk('public')->delete($path);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process document',
                    'error' => $result['error'] ?? 'OCR processing failed'
                ], 500);
            }

            // Validate extracted data
            $validation = $this->ocrService->validateExtractedData($result['structured_data']);

            // Generate form data if requested
            $formData = [];
            if ($request->boolean('auto_fill_form')) {
                $formData = $this->generateFormData($result['structured_data'], $documentType);
            }

            // Clean up the temporary file
            Storage::disk('public')->delete($path);

            return response()->json([
                'success' => true,
                'data' => [
                    'document_type' => $documentType,
                    'raw_text' => $result['raw_text'],
                    'structured_data' => $result['structured_data'],
                    'confidence' => $result['confidence'],
                    'validation' => $validation,
                    'form_data' => $formData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/batch-process-documents",
     *     summary="Process multiple documents in batch",
     *     description="Upload multiple documents and extract structured data from all",
     *     tags={"Document Processing"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="documents", type="array", @OA\Items(type="string", format="binary"), description="Document files"),
     *                 @OA\Property(property="document_types", type="array", @OA\Items(type="string"), description="Types of documents (certificate, license, etc.)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="document_type", type="string"),
     *                 @OA\Property(property="structured_data", type="object"),
     *                 @OA\Property(property="confidence", type="number"),
     *                 @OA\Property(property="validation", type="object")
     *             ))
     *         )
     *     )
     * )
     */
    public function batchProcessDocuments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:5',
            'documents.*' => 'file|mimes:jpeg,png,jpg,pdf|max:10240',
            'document_types' => 'required|array|min:1',
            'document_types.*' => 'string|in:certificate,license,id_card,degree'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $documents = $request->file('documents');
            $documentTypes = $request->document_types;
            $results = [];

            foreach ($documents as $index => $file) {
                // Store the file
                $filename = 'batch_document_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('temp/documents', $filename, 'public');

                // Process the document
                $documentType = $documentTypes[$index] ?? 'certificate';
                $result = $this->ocrService->extractDocumentData($path, $documentType);

                if ($result['success']) {
                    $validation = $this->ocrService->validateExtractedData($result['structured_data']);
                    $result['validation'] = $validation;
                }

                $results[] = $result;

                // Clean up the file
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/extract-text",
     *     summary="Extract text from document using OCR",
     *     description="Extract raw text from uploaded document using OCR",
     *     tags={"Document Processing"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="document", type="string", format="binary", description="Document file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Text extracted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="text", type="string", example="Dr. John Doe MBBS..."),
     *                 @OA\Property(property="confidence", type="number", example=0.85),
     *                 @OA\Property(property="word_count", type="integer", example=150)
     *             )
     *         )
     *     )
     * )
     */
    public function extractText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store the uploaded file
            $file = $request->file('document');
            $filename = 'text_extract_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('temp/documents', $filename, 'public');

            // Extract text
            $result = $this->ocrService->extractTextFromImage($path);

            // Clean up the file
            Storage::disk('public')->delete($path);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract text',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'text' => $result['text'],
                    'confidence' => $result['confidence'],
                    'word_count' => $result['word_count']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract text',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate form data from extracted structured data
     */
    private function generateFormData(array $structuredData, string $documentType): array
    {
        $formData = [];

        switch ($documentType) {
            case 'certificate':
            case 'license':
                $formData = [
                    'qualification' => $structuredData['qualification'] ?? $structuredData['degree'] ?? '',
                    'registration_number' => $structuredData['registration_number'] ?? $structuredData['license_number'] ?? '',
                    'title' => $this->extractTitle($structuredData['doctor_name'] ?? ''),
                    'first_name' => $this->extractFirstName($structuredData['doctor_name'] ?? ''),
                    'last_name' => $this->extractLastName($structuredData['doctor_name'] ?? ''),
                ];

                // Auto-detect specialization if qualification is available
                if (!empty($formData['qualification'])) {
                    $specializationResult = $this->specializationDetectionService->detectSpecialization($formData['qualification']);
                    if ($specializationResult['confidence'] >= 0.7) {
                        $formData['specialization'] = $specializationResult['specialization'];
                    }
                }
                break;

            case 'id_card':
                $formData = [
                    'first_name' => $this->extractFirstName($structuredData['name'] ?? ''),
                    'last_name' => $this->extractLastName($structuredData['name'] ?? ''),
                    'date_of_birth' => $structuredData['date_of_birth'] ?? '',
                    'phone' => $structuredData['phone'] ?? '',
                ];
                break;

            case 'degree':
                $formData = [
                    'qualification' => $structuredData['degree'] ?? '',
                    'first_name' => $this->extractFirstName($structuredData['student_name'] ?? ''),
                    'last_name' => $this->extractLastName($structuredData['student_name'] ?? ''),
                ];

                // Auto-detect specialization if degree is available
                if (!empty($formData['qualification'])) {
                    $specializationResult = $this->specializationDetectionService->detectSpecialization($formData['qualification']);
                    if ($specializationResult['confidence'] >= 0.7) {
                        $formData['specialization'] = $specializationResult['specialization'];
                    }
                }
                break;
        }

        // Remove empty values
        return array_filter($formData, function ($value) {
            return !empty($value);
        });
    }

    /**
     * Extract title from name
     */
    private function extractTitle(string $name): string
    {
        $titles = ['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'];
        
        foreach ($titles as $title) {
            if (stripos($name, $title) === 0) {
                return $title;
            }
        }

        return '';
    }

    /**
     * Extract first name from full name
     */
    private function extractFirstName(string $name): string
    {
        // Remove title
        $name = preg_replace('/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s*/i', '', $name);
        
        $parts = explode(' ', trim($name));
        return $parts[0] ?? '';
    }

    /**
     * Extract last name from full name
     */
    private function extractLastName(string $name): string
    {
        // Remove title
        $name = preg_replace('/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s*/i', '', $name);
        
        $parts = explode(' ', trim($name));
        if (count($parts) > 1) {
            return implode(' ', array_slice($parts, 1));
        }

        return '';
    }
}
