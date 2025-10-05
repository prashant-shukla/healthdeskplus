<?php

namespace App\Services\AI;

use App\Models\Doctor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProfileCompletenessService
{
    private $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Analyze profile completeness and generate AI-powered suggestions
     */
    public function analyzeProfileCompleteness(Doctor $doctor): array
    {
        try {
            // Get current profile data
            $profileData = $this->getProfileData($doctor);
            
            // Calculate basic completeness
            $basicAnalysis = $this->calculateBasicCompleteness($profileData);
            
            // Generate AI-powered suggestions
            $aiSuggestions = $this->generateAISuggestions($profileData, $basicAnalysis);
            
            // Combine results
            return [
                'success' => true,
                'completion_percentage' => $basicAnalysis['completion_percentage'],
                'basic_analysis' => $basicAnalysis,
                'ai_suggestions' => $aiSuggestions,
                'priority_actions' => $this->getPriorityActions($profileData, $aiSuggestions),
                'estimated_time_to_complete' => $this->estimateCompletionTime($profileData),
                'impact_score' => $this->calculateImpactScore($profileData)
            ];

        } catch (\Exception $e) {
            Log::error('Profile completeness analysis failed', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'completion_percentage' => $this->calculateBasicCompleteness($this->getProfileData($doctor))['completion_percentage'],
                'error' => 'Analysis failed, showing basic suggestions'
            ];
        }
    }

    /**
     * Get comprehensive profile data
     */
    private function getProfileData(Doctor $doctor): array
    {
        return [
            // Basic Information
            'name' => $doctor->user->name ?? '',
            'email' => $doctor->user->email ?? '',
            'phone' => $doctor->phone ?? '',
            'date_of_birth' => $doctor->date_of_birth ?? null,
            'gender' => $doctor->gender ?? '',
            'title' => $doctor->title ?? '',
            'first_name' => $doctor->first_name ?? '',
            'last_name' => $doctor->last_name ?? '',
            
            // Professional Information
            'specialization' => $doctor->specialization ?? '',
            'qualification' => $doctor->qualification ?? '',
            'registration_number' => $doctor->registration_number ?? '',
            'experience_years' => $doctor->experience_years ?? 0,
            'bio' => $doctor->bio ?? '',
            
            // Clinic Information
            'clinic_name' => $doctor->clinic_name ?? '',
            'clinic_address' => $doctor->clinic_address ?? '',
            'clinic_city' => $doctor->clinic_city ?? '',
            'clinic_state' => $doctor->clinic_state ?? '',
            'clinic_pincode' => $doctor->clinic_pincode ?? '',
            'clinic_phone' => $doctor->clinic_phone ?? '',
            'working_hours' => $doctor->working_hours ?? [],
            'consultation_fees' => $doctor->consultation_fees ?? [],
            
            // Documents and Media
            'profile_photo' => $doctor->profile_photo ?? '',
            'documents' => $doctor->documents ?? [],
            
            // Onboarding Status
            'onboarding_step' => $doctor->onboarding_step ?? 1,
            'onboarding_completed' => $doctor->onboarding_completed ?? false,
            
            // Settings and Preferences
            'settings' => $doctor->settings ?? [],
            'is_available' => $doctor->is_available ?? true
        ];
    }

    /**
     * Calculate basic completeness metrics
     */
    private function calculateBasicCompleteness(array $profileData): array
    {
        $fields = [
            // Critical fields (high weight)
            'name' => ['weight' => 3, 'required' => true],
            'email' => ['weight' => 3, 'required' => true],
            'phone' => ['weight' => 3, 'required' => true],
            'specialization' => ['weight' => 3, 'required' => true],
            'qualification' => ['weight' => 3, 'required' => true],
            'registration_number' => ['weight' => 3, 'required' => true],
            'clinic_name' => ['weight' => 3, 'required' => true],
            'clinic_address' => ['weight' => 3, 'required' => true],
            
            // Important fields (medium weight)
            'profile_photo' => ['weight' => 2, 'required' => false],
            'bio' => ['weight' => 2, 'required' => false],
            'working_hours' => ['weight' => 2, 'required' => false],
            'consultation_fees' => ['weight' => 2, 'required' => false],
            'experience_years' => ['weight' => 2, 'required' => false],
            'clinic_city' => ['weight' => 2, 'required' => false],
            'clinic_state' => ['weight' => 2, 'required' => false],
            
            // Nice-to-have fields (low weight)
            'date_of_birth' => ['weight' => 1, 'required' => false],
            'gender' => ['weight' => 1, 'required' => false],
            'clinic_pincode' => ['weight' => 1, 'required' => false],
            'clinic_phone' => ['weight' => 1, 'required' => false],
            'documents' => ['weight' => 1, 'required' => false]
        ];

        $totalWeight = 0;
        $completedWeight = 0;
        $missingFields = [];
        $completedFields = [];

        foreach ($fields as $field => $config) {
            $weight = $config['weight'];
            $totalWeight += $weight;
            
            $isCompleted = $this->isFieldCompleted($profileData[$field] ?? null, $field);
            
            if ($isCompleted) {
                $completedWeight += $weight;
                $completedFields[] = $field;
            } else {
                $missingFields[] = [
                    'field' => $field,
                    'weight' => $weight,
                    'required' => $config['required'],
                    'label' => $this->getFieldLabel($field)
                ];
            }
        }

        $completionPercentage = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

        return [
            'completion_percentage' => $completionPercentage,
            'total_fields' => count($fields),
            'completed_fields' => count($completedFields),
            'missing_fields' => $missingFields,
            'completed_fields_list' => $completedFields,
            'critical_missing' => array_filter($missingFields, fn($field) => $field['required']),
            'important_missing' => array_filter($missingFields, fn($field) => $field['weight'] >= 2 && !$field['required']),
            'nice_to_have_missing' => array_filter($missingFields, fn($field) => $field['weight'] === 1 && !$field['required'])
        ];
    }

    /**
     * Check if a field is completed
     */
    private function isFieldCompleted($value, string $field): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return !empty($value);
        }

        if (is_numeric($value)) {
            return $value > 0;
        }

        return true;
    }

    /**
     * Get field label for display
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'date_of_birth' => 'Date of Birth',
            'gender' => 'Gender',
            'title' => 'Professional Title',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'specialization' => 'Medical Specialization',
            'qualification' => 'Medical Qualification',
            'registration_number' => 'Registration Number',
            'experience_years' => 'Years of Experience',
            'bio' => 'Professional Bio',
            'clinic_name' => 'Clinic Name',
            'clinic_address' => 'Clinic Address',
            'clinic_city' => 'City',
            'clinic_state' => 'State',
            'clinic_pincode' => 'Pincode',
            'clinic_phone' => 'Clinic Phone',
            'working_hours' => 'Working Hours',
            'consultation_fees' => 'Consultation Fees',
            'profile_photo' => 'Profile Photo',
            'documents' => 'Documents'
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Generate AI-powered suggestions
     */
    private function generateAISuggestions(array $profileData, array $basicAnalysis): array
    {
        try {
            $prompt = $this->buildAISuggestionPrompt($profileData, $basicAnalysis);
            
            $response = $this->openAIService->makeChatCompletion([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical profile optimization expert. Analyze doctor profiles and provide actionable suggestions to improve completeness and patient engagement.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            if ($response['success']) {
                return $this->parseAISuggestions($response['content']);
            }

            return $this->getDefaultSuggestions($profileData, $basicAnalysis);

        } catch (\Exception $e) {
            Log::error('AI suggestions generation failed', [
                'error' => $e->getMessage()
            ]);

            return $this->getDefaultSuggestions($profileData, $basicAnalysis);
        }
    }

    /**
     * Build AI suggestion prompt
     */
    private function buildAISuggestionPrompt(array $profileData, array $basicAnalysis): string
    {
        $completion = $basicAnalysis['completion_percentage'];
        $missingFields = $basicAnalysis['missing_fields'];
        
        $prompt = "Analyze this doctor's profile and provide optimization suggestions:\n\n";
        $prompt .= "Profile Completion: {$completion}%\n\n";
        
        $prompt .= "Current Profile Data:\n";
        foreach ($profileData as $key => $value) {
            if (!empty($value)) {
                $prompt .= "- {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
        }
        
        $prompt .= "\nMissing Fields:\n";
        foreach ($missingFields as $field) {
            $prompt .= "- {$field['label']} (Weight: {$field['weight']}, Required: " . ($field['required'] ? 'Yes' : 'No') . ")\n";
        }
        
        $prompt .= "\nPlease provide:\n";
        $prompt .= "1. Top 3 priority actions to improve profile completeness\n";
        $prompt .= "2. Suggestions for better patient engagement\n";
        $prompt .= "3. Professional development recommendations\n";
        $prompt .= "4. Estimated impact of each suggestion\n\n";
        $prompt .= "Format as JSON with: priority_actions, engagement_suggestions, professional_development, impact_analysis";

        return $prompt;
    }

    /**
     * Parse AI suggestions
     */
    private function parseAISuggestions(string $content): array
    {
        // Try to parse as JSON first
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback to text parsing
        return [
            'priority_actions' => [
                'Complete missing critical fields',
                'Add professional bio',
                'Upload profile photo'
            ],
            'engagement_suggestions' => [
                'Add detailed working hours',
                'Set consultation fees',
                'Write a compelling bio'
            ],
            'professional_development' => [
                'Consider adding specializations',
                'Update qualifications',
                'Add professional achievements'
            ],
            'impact_analysis' => [
                'High impact: Complete critical fields',
                'Medium impact: Add professional details',
                'Low impact: Enhance profile presentation'
            ]
        ];
    }

    /**
     * Get default suggestions when AI fails
     */
    private function getDefaultSuggestions(array $profileData, array $basicAnalysis): array
    {
        $suggestions = [
            'priority_actions' => [],
            'engagement_suggestions' => [],
            'professional_development' => [],
            'impact_analysis' => []
        ];

        // Priority actions based on missing fields
        $criticalMissing = $basicAnalysis['critical_missing'] ?? [];
        foreach (array_slice($criticalMissing, 0, 3) as $field) {
            $suggestions['priority_actions'][] = "Complete {$field['label']}";
        }

        // Engagement suggestions
        if (empty($profileData['bio'])) {
            $suggestions['engagement_suggestions'][] = 'Add a professional bio to build trust';
        }
        if (empty($profileData['working_hours'])) {
            $suggestions['engagement_suggestions'][] = 'Set your working hours for better patient scheduling';
        }
        if (empty($profileData['consultation_fees'])) {
            $suggestions['engagement_suggestions'][] = 'Add consultation fees for transparency';
        }

        // Professional development
        if (empty($profileData['experience_years'])) {
            $suggestions['professional_development'][] = 'Add years of experience';
        }
        if (empty($profileData['documents'])) {
            $suggestions['professional_development'][] = 'Upload professional documents';
        }

        return $suggestions;
    }

    /**
     * Get priority actions
     */
    private function getPriorityActions(array $profileData, array $aiSuggestions): array
    {
        $actions = [];

        // Critical missing fields
        $criticalFields = [
            'name' => 'Complete your full name',
            'email' => 'Verify your email address',
            'phone' => 'Add your phone number',
            'specialization' => 'Select your medical specialization',
            'qualification' => 'Add your medical qualification',
            'registration_number' => 'Add your registration number',
            'clinic_name' => 'Add your clinic name',
            'clinic_address' => 'Add your clinic address'
        ];

        foreach ($criticalFields as $field => $action) {
            if (empty($profileData[$field])) {
                $actions[] = [
                    'action' => $action,
                    'field' => $field,
                    'priority' => 'high',
                    'estimated_time' => '2-5 minutes',
                    'impact' => 'Critical for profile completion'
                ];
            }
        }

        // Important fields
        $importantFields = [
            'profile_photo' => 'Upload a professional profile photo',
            'bio' => 'Write a professional bio',
            'working_hours' => 'Set your working hours',
            'consultation_fees' => 'Add consultation fees'
        ];

        foreach ($importantFields as $field => $action) {
            if (empty($profileData[$field])) {
                $actions[] = [
                    'action' => $action,
                    'field' => $field,
                    'priority' => 'medium',
                    'estimated_time' => '5-10 minutes',
                    'impact' => 'Improves patient engagement'
                ];
            }
        }

        return array_slice($actions, 0, 5); // Return top 5 actions
    }

    /**
     * Estimate completion time
     */
    private function estimateCompletionTime(array $profileData): string
    {
        $missingFields = 0;
        $timePerField = 5; // minutes

        $fieldsToCheck = [
            'name', 'email', 'phone', 'specialization', 'qualification',
            'registration_number', 'clinic_name', 'clinic_address',
            'profile_photo', 'bio', 'working_hours', 'consultation_fees'
        ];

        foreach ($fieldsToCheck as $field) {
            if (empty($profileData[$field])) {
                $missingFields++;
            }
        }

        $totalMinutes = $missingFields * $timePerField;

        if ($totalMinutes < 60) {
            return "{$totalMinutes} minutes";
        } else {
            $hours = round($totalMinutes / 60, 1);
            return "{$hours} hours";
        }
    }

    /**
     * Calculate impact score
     */
    private function calculateImpactScore(array $profileData): int
    {
        $score = 0;
        $maxScore = 100;

        // Critical fields (40 points)
        $criticalFields = ['name', 'email', 'phone', 'specialization', 'qualification', 'registration_number', 'clinic_name', 'clinic_address'];
        foreach ($criticalFields as $field) {
            if (!empty($profileData[$field])) {
                $score += 5;
            }
        }

        // Important fields (30 points)
        $importantFields = ['profile_photo', 'bio', 'working_hours', 'consultation_fees', 'experience_years'];
        foreach ($importantFields as $field) {
            if (!empty($profileData[$field])) {
                $score += 6;
            }
        }

        // Nice-to-have fields (30 points)
        $niceToHaveFields = ['date_of_birth', 'gender', 'clinic_city', 'clinic_state', 'clinic_pincode', 'clinic_phone', 'documents'];
        foreach ($niceToHaveFields as $field) {
            if (!empty($profileData[$field])) {
                $score += 4;
            }
        }

        return min($score, $maxScore);
    }

    /**
     * Get profile optimization tips
     */
    public function getOptimizationTips(Doctor $doctor): array
    {
        $profileData = $this->getProfileData($doctor);
        
        $tips = [
            'completeness' => [
                'title' => 'Profile Completeness',
                'description' => 'Complete all required fields to increase patient trust',
                'tips' => [
                    'Add a professional profile photo',
                    'Write a compelling bio',
                    'Set clear working hours',
                    'Add consultation fees'
                ]
            ],
            'engagement' => [
                'title' => 'Patient Engagement',
                'description' => 'Optimize your profile for better patient interaction',
                'tips' => [
                    'Use clear, professional language',
                    'Highlight your specializations',
                    'Add patient testimonials if available',
                    'Keep information up-to-date'
                ]
            ],
            'professional' => [
                'title' => 'Professional Development',
                'description' => 'Enhance your professional credibility',
                'tips' => [
                    'Upload relevant certificates',
                    'Add professional achievements',
                    'Keep qualifications updated',
                    'Participate in medical communities'
                ]
            ]
        ];

        return $tips;
    }
}
