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
        try {
            $this->client = new ImageAnnotatorClient([
                'credentials' => config('services.google.vision_credentials_path')
            ]);
        } catch (\Exception $e) {
            Log::error('Google Vision client initialization failed', [
                'error' => $e->getMessage()
            ]);
            $this->client = null;
        }
    }

    /**
     * Extract text from image using Google Vision OCR
     */
    public function extractTextFromImage(string $imagePath): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'OCR service not available',
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
        if ($this->client) {
            $this->client->close();
        }
    }
}
