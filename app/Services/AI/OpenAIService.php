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

            return $this->getDefaultRefinement($input, $context);
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'input' => $input,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultRefinement($input, $context);
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
     * Extract data from medical license
     */
    public function extractLicenseData(string $extractedText): array
    {
        try {
            $prompt = "Extract medical license information from this text: '{$extractedText}'. 
            Return a JSON response with:
            - doctor_name: extracted doctor name
            - license_number: license/registration number
            - qualification: medical qualification
            - issuing_authority: issuing medical council/authority
            - issue_date: issue date if found
            - expiry_date: expiry date if found
            - specialization: medical specialization if mentioned
            - confidence: confidence score (0-1)";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical license data extractor. Return only valid JSON without explanations.'
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

            return [];
        } catch (\Exception $e) {
            Log::error('OpenAI license extraction exception', [
                'extracted_text' => $extractedText,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extract data from ID card
     */
    public function extractIdCardData(string $extractedText): array
    {
        try {
            $prompt = "Extract ID card information from this text: '{$extractedText}'. 
            Return a JSON response with:
            - name: extracted name
            - id_number: ID number
            - date_of_birth: date of birth if found
            - address: address if found
            - phone: phone number if found
            - email: email if found
            - issuing_authority: issuing authority
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
                        'content' => 'You are an ID card data extractor. Return only valid JSON without explanations.'
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

            return [];
        } catch (\Exception $e) {
            Log::error('OpenAI ID card extraction exception', [
                'extracted_text' => $extractedText,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extract data from degree certificate
     */
    public function extractDegreeData(string $extractedText): array
    {
        try {
            $prompt = "Extract degree certificate information from this text: '{$extractedText}'. 
            Return a JSON response with:
            - student_name: extracted student name
            - degree: degree name (MBBS, MD, etc.)
            - specialization: specialization if mentioned
            - university: university/institution name
            - graduation_date: graduation date if found
            - grade: grade/percentage if found
            - roll_number: roll number if found
            - confidence: confidence score (0-1)";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a degree certificate data extractor. Return only valid JSON without explanations.'
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

            return [];
        } catch (\Exception $e) {
            Log::error('OpenAI degree extraction exception', [
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
        
        // Check for specific medical systems first (more specific patterns)
        if (strpos($qualification, 'BHMS') !== false || strpos($qualification, 'DHMS') !== false) {
            return [
                'specialization' => 'Homeopathy',
                'confidence' => 0.9,
                'extracted_qualifications' => [$qualification],
                'suggested_specialization_text' => 'Homeopathy'
            ];
        }
        
        if (strpos($qualification, 'BAMS') !== false || strpos($qualification, 'MD AYURVEDA') !== false) {
            return [
                'specialization' => 'Ayurveda',
                'confidence' => 0.9,
                'extracted_qualifications' => [$qualification],
                'suggested_specialization_text' => 'Ayurveda'
            ];
        }
        
        // Check for Allopathy (but exclude if it's part of other systems)
        if (strpos($qualification, 'MBBS') !== false || 
            (strpos($qualification, 'MD') !== false && strpos($qualification, 'BHMS') === false && strpos($qualification, 'BAMS') === false) ||
            (strpos($qualification, 'MS') !== false && strpos($qualification, 'BHMS') === false && strpos($qualification, 'BAMS') === false)) {
            return [
                'specialization' => 'Allopathy',
                'confidence' => 0.8,
                'extracted_qualifications' => [$qualification],
                'suggested_specialization_text' => 'Allopathy'
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

    /**
     * Get default refinement when AI fails
     */
    private function getDefaultRefinement(string $input, string $context): string
    {
        $input = trim($input);
        $originalInput = $input;
        
        // Common typos and corrections
        $corrections = [
            // Hospital names
            'applo' => 'apollo',
            'apolo' => 'apollo',
            'appolo' => 'apollo',
            'fortis' => 'fortis',
            'max' => 'max',
            'manipal' => 'manipal',
            'medanta' => 'medanta',
            'aiims' => 'aiims',
            'lilavati' => 'lilavati',
            'kokilaben' => 'kokilaben',
            
            // Common words
            'hosp' => 'hospital',
            'hospitl' => 'hospital',
            'hospit' => 'hospital',
            'clinic' => 'clinic',
            'medical' => 'medical',
            'health' => 'health',
            'care' => 'care',
            'center' => 'centre',
            'center' => 'centre',
            
            // Address terms
            'st' => 'street',
            'rd' => 'road',
            'ave' => 'avenue',
            'blvd' => 'boulevard',
            'apt' => 'apartment',
            'flr' => 'floor',
            'bldg' => 'building',
            
            // Cities
            'mumbai' => 'mumbai',
            'delhi' => 'delhi',
            'bangalore' => 'bangalore',
            'chennai' => 'chennai',
            'kolkata' => 'kolkata',
            'hyderabad' => 'hyderabad',
            'pune' => 'pune',
            'ahmedabad' => 'ahmedabad',
            'jaipur' => 'jaipur',
            'lucknow' => 'lucknow',
        ];
        
        // Apply corrections based on context
        switch ($context) {
            case 'clinic_name':
                // For clinic names, focus on hospital name corrections
                $input = strtolower($input);
                // Use word boundaries to avoid partial replacements
                foreach ($corrections as $wrong => $correct) {
                    $input = preg_replace('/\b' . preg_quote($wrong, '/') . '\b/', $correct, $input);
                }
                // Capitalize first letter of each word
                $input = ucwords($input);
                break;
                
            case 'address':
                // For addresses, focus on address term corrections
                $input = strtolower($input);
                foreach ($corrections as $wrong => $correct) {
                    $input = str_replace($wrong, $correct, $input);
                }
                // Capitalize first letter of each word
                $input = ucwords($input);
                break;
                
            case 'qualification':
                // For qualifications, focus on medical term corrections
                $input = strtoupper($input);
                $qualificationCorrections = [
                    'MBBS' => 'MBBS',
                    'MD' => 'MD',
                    'MS' => 'MS',
                    'BHMS' => 'BHMS',
                    'BAMS' => 'BAMS',
                    'DHMS' => 'DHMS',
                    'DNB' => 'DNB',
                    'MCH' => 'MCH',
                    'DM' => 'DM',
                ];
                foreach ($qualificationCorrections as $wrong => $correct) {
                    $input = str_replace($wrong, $correct, $input);
                }
                break;
                
            default:
                // General refinement
                $input = strtolower($input);
                foreach ($corrections as $wrong => $correct) {
                    $input = str_replace($wrong, $correct, $input);
                }
                $input = ucwords($input);
                break;
        }
        
        // Clean up extra spaces
        $input = preg_replace('/\s+/', ' ', trim($input));
        
        return $input;
    }

    /**
     * Make chat completion request
     */
    public function makeChatCompletion(array $params): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', $params);

            if ($response->successful()) {
                $data = $response->json();
                $content = trim($data['choices'][0]['message']['content'] ?? '');
                
                return [
                    'success' => true,
                    'content' => $content,
                    'usage' => $data['usage'] ?? []
                ];
            }

            Log::warning('OpenAI chat completion error', [
                'params' => $params,
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'content' => 'I apologize, but I\'m having trouble processing your request. Please try again.',
                'error' => 'API request failed'
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI chat completion exception', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'content' => 'I apologize, but I encountered an error. Please try again.',
                'error' => $e->getMessage()
            ];
        }
    }
}
