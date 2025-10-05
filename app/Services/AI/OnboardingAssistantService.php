<?php

namespace App\Services\AI;

use App\Models\Doctor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OnboardingAssistantService
{
    private $openAIService;
    private $googlePlacesService;
    private $specializationDetectionService;
    private $languageSupportService;

    public function __construct(
        OpenAIService $openAIService,
        GooglePlacesService $googlePlacesService,
        SpecializationDetectionService $specializationDetectionService,
        LanguageSupportService $languageSupportService
    ) {
        $this->openAIService = $openAIService;
        $this->googlePlacesService = $googlePlacesService;
        $this->specializationDetectionService = $specializationDetectionService;
        $this->languageSupportService = $languageSupportService;
    }

    /**
     * Process conversational input and generate response
     */
    public function processConversation(string $message, Doctor $doctor, array $context = [], string $language = 'en'): array
    {
        try {
            // Get current onboarding step and progress
            $currentStep = $doctor->onboarding_step ?? 1;
            $completionPercentage = $this->calculateCompletionPercentage($doctor);
            
            // Build context for AI
            $aiContext = $this->buildAIContext($doctor, $currentStep, $completionPercentage, $context, $language);
            
            // Generate AI response
            $aiResponse = $this->generateAIResponse($message, $aiContext, $language);
            
            // Extract form data from response
            $formData = $this->extractFormData($aiResponse, $doctor);
            
            // Determine next action
            $nextAction = $this->determineNextAction($aiResponse, $doctor, $currentStep);
            
            // Translate response if needed
            $response = $aiResponse['message'];
            if ($language !== 'en') {
                $translationResult = $this->languageSupportService->translate($response, $language);
                if ($translationResult['success']) {
                    $response = $translationResult['text'];
                }
            }

            // Translate suggestions if needed
            $suggestions = $aiResponse['suggestions'] ?? [];
            if ($language !== 'en' && !empty($suggestions)) {
                $translatedSuggestions = [];
                foreach ($suggestions as $suggestion) {
                    $translationResult = $this->languageSupportService->translate($suggestion, $language);
                    if ($translationResult['success']) {
                        $translatedSuggestions[] = $translationResult['text'];
                    } else {
                        $translatedSuggestions[] = $suggestion;
                    }
                }
                $suggestions = $translatedSuggestions;
            }

            return [
                'success' => true,
                'response' => $response,
                'form_data' => $formData,
                'next_action' => $nextAction,
                'current_step' => $currentStep,
                'completion_percentage' => $completionPercentage,
                'suggestions' => $suggestions,
                'requires_confirmation' => $aiResponse['requires_confirmation'] ?? false,
                'language' => $language
            ];

        } catch (\Exception $e) {
            Log::error('Onboarding assistant error', [
                'doctor_id' => $doctor->id,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'response' => 'I apologize, but I encountered an error. Please try again or contact support.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate AI response using OpenAI
     */
    private function generateAIResponse(string $message, array $context, string $language = 'en'): array
    {
        try {
            $systemPrompt = $this->getSystemPrompt($context, $language);
            $userPrompt = $this->buildUserPrompt($message, $context, $language);

            $response = $this->openAIService->makeChatCompletion([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response['success']) {
                return $this->parseAIResponse($response['content']);
            }

            return [
                'message' => 'I understand you want to continue with your registration. Could you please provide more details?',
                'suggestions' => ['Tell me about your medical qualification', 'What is your clinic name?', 'Where is your practice located?']
            ];

        } catch (\Exception $e) {
            Log::error('AI response generation failed', [
                'message' => $message,
                'context' => $context,
                'error' => $e->getMessage()
            ]);

            return [
                'message' => 'I\'m here to help you complete your registration. What would you like to tell me?',
                'suggestions' => ['My name is...', 'I have MBBS degree', 'My clinic is...']
            ];
        }
    }

    /**
     * Get system prompt for AI
     */
    private function getSystemPrompt(array $context, string $language = 'en'): string
    {
        $step = $context['current_step'];
        $completion = $context['completion_percentage'];
        $doctorData = $context['doctor_data'];

        $languageInstruction = $language !== 'en' ? "Respond in {$this->languageSupportService->getLanguageName($language)} language." : "";
        
        return "You are a helpful AI assistant for doctor registration and onboarding. {$languageInstruction}

Current context:
- Onboarding step: {$step}/4
- Completion: {$completion}%
- Doctor data: " . json_encode($doctorData) . "

Your role:
1. Help doctors complete their registration through natural conversation
2. Extract relevant information from their messages
3. Guide them through the onboarding process step by step
4. Be friendly, professional, and encouraging
5. Ask clarifying questions when needed
6. Suggest next steps based on missing information

Response format (JSON):
{
  \"message\": \"Your conversational response\",
  \"extracted_data\": {
    \"field_name\": \"extracted_value\"
  },
  \"suggestions\": [\"suggestion1\", \"suggestion2\"],
  \"requires_confirmation\": true/false,
  \"next_step\": \"step_name\"
}

Focus on:
- Step 1: Basic info (name, email, phone, specialization)
- Step 2: Professional info (qualification, registration, experience)
- Step 3: Clinic info (name, address, working hours)
- Step 4: Documents and completion

Be conversational and helpful!";
    }

    /**
     * Build user prompt
     */
    private function buildUserPrompt(string $message, array $context, string $language = 'en'): string
    {
        $doctorData = $context['doctor_data'];
        $currentStep = $context['current_step'];
        
        $prompt = "Doctor message: \"{$message}\"\n\n";
        $prompt .= "Current doctor information:\n";
        $prompt .= "- Name: " . ($doctorData['name'] ?? 'Not provided') . "\n";
        $prompt .= "- Email: " . ($doctorData['email'] ?? 'Not provided') . "\n";
        $prompt .= "- Phone: " . ($doctorData['phone'] ?? 'Not provided') . "\n";
        $prompt .= "- Specialization: " . ($doctorData['specialization'] ?? 'Not provided') . "\n";
        $prompt .= "- Qualification: " . ($doctorData['qualification'] ?? 'Not provided') . "\n";
        $prompt .= "- Clinic: " . ($doctorData['clinic_name'] ?? 'Not provided') . "\n";
        $prompt .= "- Address: " . ($doctorData['clinic_address'] ?? 'Not provided') . "\n\n";
        
        $prompt .= "Current step: {$currentStep}/4\n";
        $prompt .= "Please respond naturally and extract any relevant information from their message.";

        return $prompt;
    }

    /**
     * Parse AI response
     */
    private function parseAIResponse(string $content): array
    {
        // Try to parse as JSON first
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback to simple text response
        return [
            'message' => $content,
            'extracted_data' => [],
            'suggestions' => [],
            'requires_confirmation' => false
        ];
    }

    /**
     * Extract form data from AI response
     */
    private function extractFormData(array $aiResponse, Doctor $doctor): array
    {
        $extractedData = $aiResponse['extracted_data'] ?? [];
        $formData = [];

        // Map extracted data to form fields
        foreach ($extractedData as $key => $value) {
            switch ($key) {
                case 'name':
                    $nameParts = $this->parseName($value);
                    $formData['first_name'] = $nameParts['first_name'];
                    $formData['last_name'] = $nameParts['last_name'];
                    $formData['title'] = $nameParts['title'];
                    break;
                case 'email':
                    $formData['email'] = $value;
                    break;
                case 'phone':
                    $formData['phone'] = $value;
                    break;
                case 'specialization':
                    $formData['specialization'] = $value;
                    break;
                case 'qualification':
                    $formData['qualification'] = $value;
                    break;
                case 'registration_number':
                    $formData['registration_number'] = $value;
                    break;
                case 'experience':
                    $formData['experience_years'] = (int) $value;
                    break;
                case 'clinic_name':
                    $formData['clinic_name'] = $value;
                    break;
                case 'clinic_address':
                    $formData['clinic_address'] = $value;
                    break;
                case 'clinic_city':
                    $formData['clinic_city'] = $value;
                    break;
                case 'clinic_state':
                    $formData['clinic_state'] = $value;
                    break;
                case 'clinic_pincode':
                    $formData['clinic_pincode'] = $value;
                    break;
                case 'working_hours':
                    $formData['working_hours'] = $value;
                    break;
                case 'consultation_fees':
                    $formData['consultation_fees'] = $value;
                    break;
            }
        }

        // Auto-detect specialization if qualification is provided
        if (!empty($formData['qualification']) && empty($formData['specialization'])) {
            $specializationResult = $this->specializationDetectionService->detectSpecialization($formData['qualification']);
            if ($specializationResult['confidence'] >= 0.7) {
                $formData['specialization'] = $specializationResult['specialization'];
            }
        }

        return $formData;
    }

    /**
     * Determine next action based on AI response and current state
     */
    private function determineNextAction(array $aiResponse, Doctor $doctor, int $currentStep): array
    {
        $nextAction = [
            'type' => 'continue',
            'step' => $currentStep,
            'message' => 'Continue with current step'
        ];

        // Check if we should move to next step
        $nextStep = $aiResponse['next_step'] ?? null;
        if ($nextStep && $this->isStepComplete($doctor, $currentStep)) {
            $nextAction = [
                'type' => 'next_step',
                'step' => $currentStep + 1,
                'message' => 'Moving to next step'
            ];
        }

        // Check if we need to update profile
        if (!empty($aiResponse['extracted_data'])) {
            $nextAction['type'] = 'update_profile';
            $nextAction['message'] = 'Updating profile with extracted information';
        }

        // Check if we need confirmation
        if ($aiResponse['requires_confirmation'] ?? false) {
            $nextAction['type'] = 'confirmation_required';
            $nextAction['message'] = 'Waiting for user confirmation';
        }

        return $nextAction;
    }

    /**
     * Check if current step is complete
     */
    private function isStepComplete(Doctor $doctor, int $step): bool
    {
        switch ($step) {
            case 1:
                return !empty($doctor->user->name) && 
                       !empty($doctor->user->email) && 
                       !empty($doctor->phone) && 
                       !empty($doctor->specialization);
            case 2:
                return !empty($doctor->qualification) && 
                       !empty($doctor->registration_number);
            case 3:
                return !empty($doctor->clinic_name) && 
                       !empty($doctor->clinic_address);
            case 4:
                return !empty($doctor->profile_photo) || !empty($doctor->documents);
            default:
                return false;
        }
    }

    /**
     * Build AI context
     */
    private function buildAIContext(Doctor $doctor, int $currentStep, int $completionPercentage, array $context): array
    {
        return [
            'current_step' => $currentStep,
            'completion_percentage' => $completionPercentage,
            'doctor_data' => [
                'name' => $doctor->user->name ?? '',
                'email' => $doctor->user->email ?? '',
                'phone' => $doctor->phone ?? '',
                'specialization' => $doctor->specialization ?? '',
                'qualification' => $doctor->qualification ?? '',
                'registration_number' => $doctor->registration_number ?? '',
                'experience_years' => $doctor->experience_years ?? 0,
                'clinic_name' => $doctor->clinic_name ?? '',
                'clinic_address' => $doctor->clinic_address ?? '',
                'clinic_city' => $doctor->clinic_city ?? '',
                'clinic_state' => $doctor->clinic_state ?? '',
                'clinic_pincode' => $doctor->clinic_pincode ?? '',
                'working_hours' => $doctor->working_hours ?? [],
                'consultation_fees' => $doctor->consultation_fees ?? [],
            ],
            'context' => $context
        ];
    }

    /**
     * Calculate completion percentage
     */
    private function calculateCompletionPercentage(Doctor $doctor): int
    {
        $totalFields = 12;
        $completedFields = 0;

        // Basic info
        if (!empty($doctor->user->name)) $completedFields++;
        if (!empty($doctor->user->email)) $completedFields++;
        if (!empty($doctor->phone)) $completedFields++;
        if (!empty($doctor->specialization)) $completedFields++;

        // Professional info
        if (!empty($doctor->qualification)) $completedFields++;
        if (!empty($doctor->registration_number)) $completedFields++;
        if (!empty($doctor->experience_years)) $completedFields++;

        // Clinic info
        if (!empty($doctor->clinic_name)) $completedFields++;
        if (!empty($doctor->clinic_address)) $completedFields++;
        if (!empty($doctor->clinic_city)) $completedFields++;

        // Documents
        if (!empty($doctor->profile_photo)) $completedFields++;
        if (!empty($doctor->documents)) $completedFields++;

        return min(100, round(($completedFields / $totalFields) * 100));
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

    /**
     * Get conversation suggestions based on current step
     */
    public function getConversationSuggestions(Doctor $doctor, string $language = 'en'): array
    {
        $step = $doctor->onboarding_step ?? 1;
        $suggestions = [];

        switch ($step) {
            case 1:
                $suggestions = [
                    'Hi! I\'m Dr. John Doe',
                    'I have MBBS and MD degrees',
                    'I specialize in Cardiology',
                    'My phone number is +91-9876543210'
                ];
                break;
            case 2:
                $suggestions = [
                    'I have MBBS from AIIMS and MD in Cardiology',
                    'My registration number is ABC123456',
                    'I have 10 years of experience',
                    'I completed my MBBS in 2010'
                ];
                break;
            case 3:
                $suggestions = [
                    'My clinic is called City Medical Center',
                    'I practice at 123 Main Street, Mumbai',
                    'My clinic is in Bandra, Mumbai',
                    'I work from 9 AM to 6 PM'
                ];
                break;
            case 4:
                $suggestions = [
                    'I have uploaded my documents',
                    'My profile is complete',
                    'I\'m ready to start seeing patients'
                ];
                break;
        }

        // Translate suggestions if needed
        if ($language !== 'en' && !empty($suggestions)) {
            $translatedSuggestions = [];
            foreach ($suggestions as $suggestion) {
                $translationResult = $this->languageSupportService->translate($suggestion, $language);
                if ($translationResult['success']) {
                    $translatedSuggestions[] = $translationResult['text'];
                } else {
                    $translatedSuggestions[] = $suggestion;
                }
            }
            $suggestions = $translatedSuggestions;
        }

        return $suggestions;
    }

    /**
     * Get onboarding progress summary
     */
    public function getProgressSummary(Doctor $doctor, string $language = 'en'): array
    {
        $step = $doctor->onboarding_step ?? 1;
        $completionPercentage = $this->calculateCompletionPercentage($doctor);

        $steps = [
            1 => [
                'title' => 'Basic Information',
                'description' => 'Tell us about yourself',
                'fields' => ['name', 'email', 'phone', 'specialization'],
                'completed' => $this->isStepComplete($doctor, 1)
            ],
            2 => [
                'title' => 'Professional Details',
                'description' => 'Share your medical qualifications',
                'fields' => ['qualification', 'registration_number', 'experience'],
                'completed' => $this->isStepComplete($doctor, 2)
            ],
            3 => [
                'title' => 'Clinic Information',
                'description' => 'Tell us about your practice',
                'fields' => ['clinic_name', 'clinic_address', 'working_hours'],
                'completed' => $this->isStepComplete($doctor, 3)
            ],
            4 => [
                'title' => 'Documents & Completion',
                'description' => 'Upload documents and complete setup',
                'fields' => ['profile_photo', 'documents'],
                'completed' => $this->isStepComplete($doctor, 4)
            ]
        ];

        // Translate step titles and descriptions if needed
        if ($language !== 'en') {
            foreach ($steps as $stepNumber => &$stepData) {
                // Translate title
                $titleResult = $this->languageSupportService->translate($stepData['title'], $language);
                if ($titleResult['success']) {
                    $stepData['title'] = $titleResult['text'];
                }
                
                // Translate description
                $descResult = $this->languageSupportService->translate($stepData['description'], $language);
                if ($descResult['success']) {
                    $stepData['description'] = $descResult['text'];
                }
            }
        }

        return [
            'current_step' => $step,
            'completion_percentage' => $completionPercentage,
            'steps' => $steps,
            'next_step' => $step < 4 ? $step + 1 : null,
            'is_complete' => $completionPercentage >= 100,
            'language' => $language
        ];
    }
}
