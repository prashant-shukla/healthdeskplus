<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Refine messy input using OpenAI
     */
    public function refineInput(string $input, string $context = 'general'): string
    {
        try {
            $prompt = $this->getRefinementPrompt($input, $context);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that refines and corrects user input for medical registration forms. Return only the corrected text without explanations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['message']['content'] ?? $input);
            }

            Log::warning('OpenAI API error', [
                'input' => $input,
                'response' => $response->json()
            ]);

            return $input;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'input' => $input,
                'error' => $e->getMessage()
            ]);
            return $input;
        }
    }

    /**
     * Detect specialization from qualification text
     */
    public function detectSpecialization(string $qualification): array
    {
        try {
            $prompt = "Analyze this medical qualification text and determine the specialization: '{$qualification}'. 
            Return a JSON response with:
            - specialization: one of 'Allopathy', 'Homeopathy', 'Ayurveda', or 'Unknown'
            - confidence: confidence score (0-1)
            - extracted_qualifications: array of recognized qualifications
            - suggested_specialization_text: cleaned specialization text";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical qualification analyzer. Return only valid JSON without explanations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = trim($data['choices'][0]['message']['content'] ?? '{}');
                
                $result = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }

            Log::warning('OpenAI specialization detection error', [
                'qualification' => $qualification,
                'response' => $response->json()
            ]);

            return $this->getDefaultSpecializationResult($qualification);
        } catch (\Exception $e) {
            Log::error('OpenAI specialization detection exception', [
                'qualification' => $qualification,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultSpecializationResult($qualification);
        }
    }

    /**
     * Extract and normalize data from certificate text
     */
    public function extractCertificateData(string $extractedText): array
    {
        try {
            $prompt = "Extract medical certificate information from this text: '{$extractedText}'. 
            Return a JSON response with:
            - doctor_name: extracted doctor name
            - registration_number: registration/license number
            - qualification: medical qualification
            - issuing_authority: issuing medical council/authority
            - issue_date: issue date if found
            - expiry_date: expiry date if found
            - confidence: confidence score (0-1)";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical certificate data extractor. Return only valid JSON without explanations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = trim($data['choices'][0]['message']['content'] ?? '{}');
                
                $result = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }

            Log::warning('OpenAI certificate extraction error', [
                'extracted_text' => $extractedText,
                'response' => $response->json()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('OpenAI certificate extraction exception', [
                'extracted_text' => $extractedText,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Generate profile completeness suggestions
     */
    public function generateProfileSuggestions(array $profileData): array
    {
        try {
            $prompt = "Analyze this doctor profile data and suggest next steps for completion: " . json_encode($profileData) . "
            Return a JSON response with:
            - completion_percentage: estimated completion percentage
            - next_steps: array of suggested next steps with priority
            - missing_critical_fields: array of critical missing fields
            - suggestions: array of helpful suggestions";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical profile completion assistant. Return only valid JSON without explanations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 400,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = trim($data['choices'][0]['message']['content'] ?? '{}');
                
                $result = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }

            Log::warning('OpenAI profile suggestions error', [
                'profile_data' => $profileData,
                'response' => $response->json()
            ]);

            return $this->getDefaultProfileSuggestions($profileData);
        } catch (\Exception $e) {
            Log::error('OpenAI profile suggestions exception', [
                'profile_data' => $profileData,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultProfileSuggestions($profileData);
        }
    }

    /**
     * Get refinement prompt based on context
     */
    private function getRefinementPrompt(string $input, string $context): string
    {
        switch ($context) {
            case 'clinic_name':
                return "Correct and standardize this clinic/hospital name: '{$input}'. Return only the corrected name.";
            case 'address':
                return "Correct and standardize this address: '{$input}'. Return only the corrected address.";
            case 'qualification':
                return "Correct and standardize this medical qualification: '{$input}'. Return only the corrected qualification.";
            default:
                return "Correct and standardize this text: '{$input}'. Return only the corrected text.";
        }
    }

    /**
     * Get default specialization result when AI fails
     */
    private function getDefaultSpecializationResult(string $qualification): array
    {
        $qualification = strtoupper($qualification);
        
        // Simple rule-based fallback
        if (strpos($qualification, 'MBBS') !== false || strpos($qualification, 'MD') !== false || strpos($qualification, 'MS') !== false) {
            return [
                'specialization' => 'Allopathy',
                'confidence' => 0.8,
                'extracted_qualifications' => [$qualification],
                'suggested_specialization_text' => 'Allopathy'
            ];
        }
        
        if (strpos($qualification, 'BHMS') !== false || strpos($qualification, 'DHMS') !== false) {
            return [
                'specialization' => 'Homeopathy',
                'confidence' => 0.8,
                'extracted_qualifications' => [$qualification],
                'suggested_specialization_text' => 'Homeopathy'
            ];
        }
        
        if (strpos($qualification, 'BAMS') !== false || strpos($qualification, 'MD AYURVEDA') !== false) {
            return [
                'specialization' => 'Ayurveda',
                'confidence' => 0.8,
                'extracted_qualifications' => [$qualification],
                'suggested_specialization_text' => 'Ayurveda'
            ];
        }

        return [
            'specialization' => 'Unknown',
            'confidence' => 0.3,
            'extracted_qualifications' => [$qualification],
            'suggested_specialization_text' => 'Unknown'
        ];
    }

    /**
     * Get default profile suggestions when AI fails
     */
    private function getDefaultProfileSuggestions(array $profileData): array
    {
        $missingFields = [];
        $suggestions = [];

        if (empty($profileData['qualification'])) {
            $missingFields[] = 'qualification';
            $suggestions[] = 'Add your medical qualification (MBBS, MD, etc.)';
        }

        if (empty($profileData['registration_number'])) {
            $missingFields[] = 'registration_number';
            $suggestions[] = 'Add your medical registration number';
        }

        if (empty($profileData['clinic_name'])) {
            $missingFields[] = 'clinic_name';
            $suggestions[] = 'Add your clinic or hospital name';
        }

        if (empty($profileData['clinic_address'])) {
            $missingFields[] = 'clinic_address';
            $suggestions[] = 'Add your clinic address';
        }

        $completionPercentage = max(20, 100 - (count($missingFields) * 20));

        return [
            'completion_percentage' => $completionPercentage,
            'next_steps' => $suggestions,
            'missing_critical_fields' => $missingFields,
            'suggestions' => $suggestions
        ];
    }
}
