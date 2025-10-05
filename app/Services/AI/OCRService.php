<?php

namespace App\Services\AI;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OCRService
{
    private $client;

    public function __construct()
    {
        // Lazy initialization - only create client when needed
        $this->client = null;
    }

    /**
     * Initialize the Google Vision client
     */
    private function initializeClient()
    {
        if ($this->client === null) {
            try {
                $credentialsPath = config('services.google.vision_credentials_path');
                
                if (!$credentialsPath || !file_exists($credentialsPath)) {
                    Log::warning('Google Vision credentials not configured or file not found', [
                        'credentials_path' => $credentialsPath
                    ]);
                    return false;
                }

                $this->client = new ImageAnnotatorClient([
                    'credentials' => $credentialsPath
                ]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Google Vision client initialization failed', [
                    'error' => $e->getMessage()
                ]);
                $this->client = false; // Mark as failed
                return false;
            }
        }
        
        return $this->client !== false;
    }

    /**
     * Extract text from image using Google Vision OCR
     */
    public function extractTextFromImage(string $imagePath): array
    {
        if (!$this->initializeClient()) {
            return [
                'success' => false,
                'error' => 'OCR service not available - Google Vision credentials not configured',
                'text' => ''
            ];
        }

        try {
            // Read image file
            $imageContent = Storage::get($imagePath);
            
            if (!$imageContent) {
                return [
                    'success' => false,
                    'error' => 'Image file not found',
                    'text' => ''
                ];
            }

            // Perform OCR
            $image = (new \Google\Cloud\Vision\V1\Image())
                ->setContent($imageContent);

            $feature = (new \Google\Cloud\Vision\V1\Feature())
                ->setType(Type::DOCUMENT_TEXT_DETECTION);

            $request = (new \Google\Cloud\Vision\V1\AnnotateImageRequest())
                ->setImage($image)
                ->setFeatures([$feature]);

            $response = $this->client->annotateImage($request);
            $annotations = $response->getTextAnnotations();

            if (count($annotations) > 0) {
                $text = $annotations[0]->getDescription();
                
                return [
                    'success' => true,
                    'text' => $text,
                    'confidence' => $this->calculateConfidence($annotations),
                    'word_count' => str_word_count($text),
                    'raw_annotations' => $this->formatAnnotations($annotations)
                ];
            }

            return [
                'success' => false,
                'error' => 'No text detected in image',
                'text' => ''
            ];

        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'text' => ''
            ];
        }
    }

    /**
     * Extract text from multiple images
     */
    public function extractTextFromImages(array $imagePaths): array
    {
        $results = [];
        
        foreach ($imagePaths as $index => $imagePath) {
            $results[$index] = $this->extractTextFromImage($imagePath);
        }

        return $results;
    }

    /**
     * Extract structured data from medical certificate
     */
    public function extractCertificateData(string $imagePath): array
    {
        $ocrResult = $this->extractTextFromImage($imagePath);
        
        if (!$ocrResult['success']) {
            return $ocrResult;
        }

        // Use OpenAI to structure the extracted text
        $openAIService = new OpenAIService();
        $structuredData = $openAIService->extractCertificateData($ocrResult['text']);

        return [
            'success' => true,
            'raw_text' => $ocrResult['text'],
            'structured_data' => $structuredData,
            'confidence' => $ocrResult['confidence']
        ];
    }

    /**
     * Extract data from multiple document types
     */
    public function extractDocumentData(string $imagePath, string $documentType = 'certificate'): array
    {
        $ocrResult = $this->extractTextFromImage($imagePath);
        
        if (!$ocrResult['success']) {
            return $ocrResult;
        }

        $openAIService = new OpenAIService();
        
        switch ($documentType) {
            case 'certificate':
                $structuredData = $openAIService->extractCertificateData($ocrResult['text']);
                break;
            case 'license':
                $structuredData = $openAIService->extractLicenseData($ocrResult['text']);
                break;
            case 'id_card':
                $structuredData = $openAIService->extractIdCardData($ocrResult['text']);
                break;
            case 'degree':
                $structuredData = $openAIService->extractDegreeData($ocrResult['text']);
                break;
            default:
                $structuredData = $openAIService->extractCertificateData($ocrResult['text']);
        }

        return [
            'success' => true,
            'document_type' => $documentType,
            'raw_text' => $ocrResult['text'],
            'structured_data' => $structuredData,
            'confidence' => $ocrResult['confidence'],
            'word_count' => $ocrResult['word_count']
        ];
    }

    /**
     * Batch process multiple documents
     */
    public function batchExtractDocuments(array $documentPaths): array
    {
        $results = [];
        
        foreach ($documentPaths as $index => $document) {
            $imagePath = $document['path'] ?? $document;
            $documentType = $document['type'] ?? 'certificate';
            
            $results[$index] = $this->extractDocumentData($imagePath, $documentType);
        }

        return $results;
    }

    /**
     * Validate extracted data quality
     */
    public function validateExtractedData(array $structuredData): array
    {
        $validation = [
            'is_valid' => true,
            'confidence_score' => 0.0,
            'missing_fields' => [],
            'warnings' => [],
            'suggestions' => []
        ];

        // Check for required fields based on document type
        $requiredFields = [
            'doctor_name' => 'Doctor name',
            'registration_number' => 'Registration number',
            'qualification' => 'Qualification'
        ];

        $confidenceFactors = [];
        $totalFields = count($requiredFields);
        $foundFields = 0;

        foreach ($requiredFields as $field => $label) {
            if (!empty($structuredData[$field])) {
                $foundFields++;
                $confidenceFactors[] = 1.0;
            } else {
                $validation['missing_fields'][] = $label;
                $confidenceFactors[] = 0.0;
            }
        }

        // Calculate confidence score
        $validation['confidence_score'] = array_sum($confidenceFactors) / $totalFields;

        // Add warnings and suggestions
        if ($validation['confidence_score'] < 0.5) {
            $validation['warnings'][] = 'Low confidence in extracted data. Please verify manually.';
            $validation['suggestions'][] = 'Consider uploading a clearer image or different document.';
        }

        if (count($validation['missing_fields']) > 0) {
            $validation['warnings'][] = 'Some required fields are missing from the document.';
            $validation['suggestions'][] = 'Please fill in the missing fields manually.';
        }

        $validation['is_valid'] = $validation['confidence_score'] >= 0.7 && count($validation['missing_fields']) <= 1;

        return $validation;
    }

    /**
     * Calculate confidence score from annotations
     */
    private function calculateConfidence($annotations): float
    {
        if (count($annotations) <= 1) {
            return 0.5; // Default confidence for single annotation
        }

        $totalConfidence = 0;
        $count = 0;

        // Skip the first annotation (full text) and calculate average confidence
        for ($i = 1; $i < count($annotations); $i++) {
            $annotation = $annotations[$i];
            // Note: Google Vision doesn't provide confidence scores in the same way
            // This is a simplified calculation
            $totalConfidence += 0.8; // Default confidence
            $count++;
        }

        return $count > 0 ? $totalConfidence / $count : 0.5;
    }

    /**
     * Format annotations for better processing
     */
    private function formatAnnotations($annotations): array
    {
        $formatted = [];
        
        foreach ($annotations as $annotation) {
            $formatted[] = [
                'text' => $annotation->getDescription(),
                'bounding_poly' => $this->formatBoundingPoly($annotation->getBoundingPoly()),
            ];
        }

        return $formatted;
    }

    /**
     * Format bounding polygon
     */
    private function formatBoundingPoly($boundingPoly): array
    {
        if (!$boundingPoly) {
            return [];
        }

        $vertices = [];
        foreach ($boundingPoly->getVertices() as $vertex) {
            $vertices[] = [
                'x' => $vertex->getX(),
                'y' => $vertex->getY(),
            ];
        }

        return $vertices;
    }

    /**
     * Clean up client resources
     */
    public function __destruct()
    {
        if ($this->client && $this->client !== false) {
            try {
                $this->client->close();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
}
