<?php

namespace App\Services\AI;

use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LanguageSupportService
{
    private $translateClient;
    private $supportedLanguages;
    private $defaultLanguage;

    public function __construct()
    {
        $this->defaultLanguage = 'en';
        $this->supportedLanguages = $this->getSupportedLanguages();
        
        try {
            $apiKey = config('services.google.translate_api_key');
            if (!$apiKey) {
                throw new \Exception('Google Translate API key not configured');
            }
            
            $this->translateClient = new TranslateClient([
                'key' => $apiKey
            ]);
            
            Log::info('Google Translate client initialized successfully');
        } catch (\Exception $e) {
            Log::error('Google Translate client initialization failed', [
                'error' => $e->getMessage(),
                'api_key' => config('services.google.translate_api_key') ? 'configured' : 'not configured'
            ]);
            $this->translateClient = null;
        }
    }

    /**
     * Translate text to target language
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = null): array
    {
        if (!$this->translateClient) {
            return [
                'success' => false,
                'text' => $text,
                'error' => 'Translation service not available'
            ];
        }

        try {
            // Check if translation is needed
            if ($targetLanguage === $this->defaultLanguage || $targetLanguage === $sourceLanguage) {
                return [
                    'success' => true,
                    'text' => $text,
                    'source_language' => $sourceLanguage ?? $this->defaultLanguage,
                    'target_language' => $targetLanguage,
                    'translated' => false
                ];
            }

            // Check cache first
            $cacheKey = 'translation_' . md5($text . $targetLanguage . ($sourceLanguage ?? 'auto'));
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            // Log translation attempt
            Log::info('Attempting translation', [
                'text' => $text,
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage,
                'client_available' => $this->translateClient !== null
            ]);

            // Perform translation
            try {
                $result = $this->translateClient->translate($text, [
                    'target' => $targetLanguage,
                    'source' => $sourceLanguage
                ]);

                $translatedText = $result['text'] ?? $text;
                $detectedSourceLanguage = $result['source'] ?? $sourceLanguage;
            } catch (\Exception $clientError) {
                Log::warning('Google Translate client failed, trying direct API call', [
                    'error' => $clientError->getMessage()
                ]);
                
                // Fallback to direct API call
                $result = $this->translateDirectAPI($text, $targetLanguage, $sourceLanguage);
                $translatedText = $result['text'] ?? $text;
                $detectedSourceLanguage = $result['source'] ?? $sourceLanguage;
            }

            $response = [
                'success' => true,
                'text' => $translatedText,
                'source_language' => $detectedSourceLanguage,
                'target_language' => $targetLanguage,
                'translated' => true,
                'confidence' => $result['confidence'] ?? 1.0
            ];

            // Cache the result
            Cache::put($cacheKey, $response, 3600); // Cache for 1 hour

            return $response;

        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'text' => $text,
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'text' => $text,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Translate multiple texts
     */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = null): array
    {
        $results = [];
        
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLanguage, $sourceLanguage);
        }

        return $results;
    }

    /**
     * Detect language of text
     */
    public function detectLanguage(string $text): array
    {
        if (!$this->translateClient) {
            return [
                'success' => false,
                'language' => $this->defaultLanguage,
                'confidence' => 0.0,
                'error' => 'Language detection service not available'
            ];
        }

        try {
            $result = $this->translateClient->detectLanguage($text);
            
            return [
                'success' => true,
                'language' => $result['languageCode'] ?? $this->defaultLanguage,
                'confidence' => $result['confidence'] ?? 0.0
            ];

        } catch (\Exception $e) {
            Log::error('Language detection failed', [
                'text' => $text,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'language' => $this->defaultLanguage,
                'confidence' => 0.0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return [
            'en' => ['name' => 'English', 'native_name' => 'English'],
            'hi' => ['name' => 'Hindi', 'native_name' => 'हिन्दी'],
            'ta' => ['name' => 'Tamil', 'native_name' => 'தமிழ்'],
            'te' => ['name' => 'Telugu', 'native_name' => 'తెలుగు'],
            'bn' => ['name' => 'Bengali', 'native_name' => 'বাংলা'],
            'mr' => ['name' => 'Marathi', 'native_name' => 'मराठी'],
            'gu' => ['name' => 'Gujarati', 'native_name' => 'ગુજરાતી'],
            'kn' => ['name' => 'Kannada', 'native_name' => 'ಕನ್ನಡ'],
            'ml' => ['name' => 'Malayalam', 'native_name' => 'മലയാളം'],
            'pa' => ['name' => 'Punjabi', 'native_name' => 'ਪੰਜਾਬੀ'],
            'or' => ['name' => 'Odia', 'native_name' => 'ଓଡ଼ିଆ'],
            'as' => ['name' => 'Assamese', 'native_name' => 'অসমীয়া'],
            'ur' => ['name' => 'Urdu', 'native_name' => 'اردو'],
            'ne' => ['name' => 'Nepali', 'native_name' => 'नेपाली'],
            'si' => ['name' => 'Sinhala', 'native_name' => 'සිංහල'],
            'my' => ['name' => 'Burmese', 'native_name' => 'မြန်မာ'],
            'th' => ['name' => 'Thai', 'native_name' => 'ไทย'],
            'vi' => ['name' => 'Vietnamese', 'native_name' => 'Tiếng Việt'],
            'id' => ['name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia'],
            'ms' => ['name' => 'Malay', 'native_name' => 'Bahasa Melayu'],
            'fil' => ['name' => 'Filipino', 'native_name' => 'Filipino'],
            'zh' => ['name' => 'Chinese', 'native_name' => '中文'],
            'ja' => ['name' => 'Japanese', 'native_name' => '日本語'],
            'ko' => ['name' => 'Korean', 'native_name' => '한국어'],
            'ar' => ['name' => 'Arabic', 'native_name' => 'العربية'],
            'fa' => ['name' => 'Persian', 'native_name' => 'فارسی'],
            'tr' => ['name' => 'Turkish', 'native_name' => 'Türkçe'],
            'ru' => ['name' => 'Russian', 'native_name' => 'Русский'],
            'de' => ['name' => 'German', 'native_name' => 'Deutsch'],
            'fr' => ['name' => 'French', 'native_name' => 'Français'],
            'es' => ['name' => 'Spanish', 'native_name' => 'Español'],
            'it' => ['name' => 'Italian', 'native_name' => 'Italiano'],
            'pt' => ['name' => 'Portuguese', 'native_name' => 'Português'],
            'nl' => ['name' => 'Dutch', 'native_name' => 'Nederlands'],
            'sv' => ['name' => 'Swedish', 'native_name' => 'Svenska'],
            'da' => ['name' => 'Danish', 'native_name' => 'Dansk'],
            'no' => ['name' => 'Norwegian', 'native_name' => 'Norsk'],
            'fi' => ['name' => 'Finnish', 'native_name' => 'Suomi'],
            'pl' => ['name' => 'Polish', 'native_name' => 'Polski'],
            'cs' => ['name' => 'Czech', 'native_name' => 'Čeština'],
            'hu' => ['name' => 'Hungarian', 'native_name' => 'Magyar'],
            'ro' => ['name' => 'Romanian', 'native_name' => 'Română'],
            'bg' => ['name' => 'Bulgarian', 'native_name' => 'Български'],
            'hr' => ['name' => 'Croatian', 'native_name' => 'Hrvatski'],
            'sk' => ['name' => 'Slovak', 'native_name' => 'Slovenčina'],
            'sl' => ['name' => 'Slovenian', 'native_name' => 'Slovenščina'],
            'et' => ['name' => 'Estonian', 'native_name' => 'Eesti'],
            'lv' => ['name' => 'Latvian', 'native_name' => 'Latviešu'],
            'lt' => ['name' => 'Lithuanian', 'native_name' => 'Lietuvių'],
            'el' => ['name' => 'Greek', 'native_name' => 'Ελληνικά'],
            'he' => ['name' => 'Hebrew', 'native_name' => 'עברית'],
            'sw' => ['name' => 'Swahili', 'native_name' => 'Kiswahili'],
            'am' => ['name' => 'Amharic', 'native_name' => 'አማርኛ'],
            'yo' => ['name' => 'Yoruba', 'native_name' => 'Yorùbá'],
            'ig' => ['name' => 'Igbo', 'native_name' => 'Igbo'],
            'ha' => ['name' => 'Hausa', 'native_name' => 'Hausa'],
            'zu' => ['name' => 'Zulu', 'native_name' => 'IsiZulu'],
            'xh' => ['name' => 'Xhosa', 'native_name' => 'IsiXhosa'],
            'af' => ['name' => 'Afrikaans', 'native_name' => 'Afrikaans']
        ];
    }

    /**
     * Get language name by code
     */
    public function getLanguageName(string $languageCode): string
    {
        return $this->supportedLanguages[$languageCode]['name'] ?? $languageCode;
    }

    /**
     * Get native language name by code
     */
    public function getNativeLanguageName(string $languageCode): string
    {
        return $this->supportedLanguages[$languageCode]['native_name'] ?? $languageCode;
    }

    /**
     * Check if language is supported
     */
    public function isLanguageSupported(string $languageCode): bool
    {
        return isset($this->supportedLanguages[$languageCode]);
    }

    /**
     * Translate form labels and messages
     */
    public function translateFormLabels(array $labels, string $targetLanguage): array
    {
        $translatedLabels = [];
        
        foreach ($labels as $key => $label) {
            $result = $this->translate($label, $targetLanguage);
            $translatedLabels[$key] = $result['text'];
        }

        return $translatedLabels;
    }

    /**
     * Translate AI responses
     */
    public function translateAIResponse(array $response, string $targetLanguage): array
    {
        if ($targetLanguage === $this->defaultLanguage) {
            return $response;
        }

        $translatedResponse = $response;

        // Translate main message
        if (isset($response['response'])) {
            $result = $this->translate($response['response'], $targetLanguage);
            $translatedResponse['response'] = $result['text'];
        }

        // Translate suggestions
        if (isset($response['suggestions']) && is_array($response['suggestions'])) {
            $translatedSuggestions = [];
            foreach ($response['suggestions'] as $suggestion) {
                $result = $this->translate($suggestion, $targetLanguage);
                $translatedSuggestions[] = $result['text'];
            }
            $translatedResponse['suggestions'] = $translatedSuggestions;
        }

        // Translate priority actions
        if (isset($response['priority_actions']) && is_array($response['priority_actions'])) {
            $translatedActions = [];
            foreach ($response['priority_actions'] as $action) {
                if (is_string($action)) {
                    $result = $this->translate($action, $targetLanguage);
                    $translatedActions[] = $result['text'];
                } elseif (is_array($action) && isset($action['action'])) {
                    $result = $this->translate($action['action'], $targetLanguage);
                    $action['action'] = $result['text'];
                    $translatedActions[] = $action;
                }
            }
            $translatedResponse['priority_actions'] = $translatedActions;
        }

        return $translatedResponse;
    }

    /**
     * Translate onboarding messages
     */
    public function translateOnboardingMessages(array $messages, string $targetLanguage): array
    {
        $translatedMessages = [];
        
        foreach ($messages as $key => $message) {
            if (is_string($message)) {
                $result = $this->translate($message, $targetLanguage);
                $translatedMessages[$key] = $result['text'];
            } elseif (is_array($message)) {
                $translatedMessages[$key] = $this->translateOnboardingMessages($message, $targetLanguage);
            }
        }

        return $translatedMessages;
    }

    /**
     * Get language preferences for a region
     */
    public function getLanguagePreferencesForRegion(string $region): array
    {
        $preferences = [
            'IN' => ['hi', 'en', 'ta', 'te', 'bn', 'mr', 'gu', 'kn', 'ml', 'pa'],
            'PK' => ['ur', 'en', 'pa', 'sd', 'ps'],
            'BD' => ['bn', 'en'],
            'LK' => ['si', 'ta', 'en'],
            'NP' => ['ne', 'en', 'hi'],
            'MM' => ['my', 'en'],
            'TH' => ['th', 'en'],
            'VN' => ['vi', 'en'],
            'ID' => ['id', 'en'],
            'MY' => ['ms', 'en', 'zh', 'ta'],
            'PH' => ['fil', 'en', 'zh'],
            'CN' => ['zh', 'en'],
            'JP' => ['ja', 'en'],
            'KR' => ['ko', 'en'],
            'AE' => ['ar', 'en', 'ur', 'hi'],
            'SA' => ['ar', 'en'],
            'EG' => ['ar', 'en'],
            'TR' => ['tr', 'en'],
            'RU' => ['ru', 'en'],
            'DE' => ['de', 'en'],
            'FR' => ['fr', 'en'],
            'ES' => ['es', 'en'],
            'IT' => ['it', 'en'],
            'PT' => ['pt', 'en'],
            'NL' => ['nl', 'en'],
            'SE' => ['sv', 'en'],
            'DK' => ['da', 'en'],
            'NO' => ['no', 'en'],
            'FI' => ['fi', 'en'],
            'PL' => ['pl', 'en'],
            'CZ' => ['cs', 'en'],
            'HU' => ['hu', 'en'],
            'RO' => ['ro', 'en'],
            'BG' => ['bg', 'en'],
            'HR' => ['hr', 'en'],
            'SK' => ['sk', 'en'],
            'SI' => ['sl', 'en'],
            'EE' => ['et', 'en'],
            'LV' => ['lv', 'en'],
            'LT' => ['lt', 'en'],
            'GR' => ['el', 'en'],
            'IL' => ['he', 'en', 'ar'],
            'KE' => ['sw', 'en'],
            'ET' => ['am', 'en'],
            'NG' => ['yo', 'ig', 'ha', 'en'],
            'ZA' => ['zu', 'xh', 'af', 'en']
        ];

        return $preferences[$region] ?? ['en'];
    }

    /**
     * Detect user's preferred language from request
     */
    public function detectUserLanguage(\Illuminate\Http\Request $request): string
    {
        // Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $languages = explode(',', $acceptLanguage);
            foreach ($languages as $lang) {
                $lang = trim(explode(';', $lang)[0]);
                $lang = strtolower($lang);
                
                // Check for exact match
                if ($this->isLanguageSupported($lang)) {
                    return $lang;
                }
                
                // Check for language code only (e.g., 'hi' from 'hi-IN')
                $langCode = explode('-', $lang)[0];
                if ($this->isLanguageSupported($langCode)) {
                    return $langCode;
                }
            }
        }

        // Check query parameter
        $langParam = $request->query('lang');
        if ($langParam && $this->isLanguageSupported($langParam)) {
            return $langParam;
        }

        // Check user's stored preference
        $user = $request->user();
        if ($user && isset($user->language_preference)) {
            return $user->language_preference;
        }

        // Default to English
        return $this->defaultLanguage;
    }

    /**
     * Direct API call to Google Translate (fallback method)
     */
    private function translateDirectAPI(string $text, string $targetLanguage, string $sourceLanguage = null): array
    {
        try {
            $apiKey = config('services.google.translate_api_key');
            if (!$apiKey) {
                throw new \Exception('Google Translate API key not configured');
            }

            $url = 'https://translation.googleapis.com/language/translate/v2';
            $data = [
                'q' => $text,
                'target' => $targetLanguage,
                'key' => $apiKey
            ];

            if ($sourceLanguage) {
                $data['source'] = $sourceLanguage;
            }

            $response = \Http::post($url, $data);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['data']['translations'][0])) {
                    $translation = $result['data']['translations'][0];
                    return [
                        'text' => $translation['translatedText'],
                        'source' => $sourceLanguage ?? $translation['detectedSourceLanguage'] ?? 'auto',
                        'confidence' => 1.0
                    ];
                }
            }

            throw new \Exception('Translation API call failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Direct Google Translate API call failed', [
                'error' => $e->getMessage(),
                'text' => $text,
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage
            ]);

            return [
                'text' => $text,
                'source' => $sourceLanguage ?? 'auto',
                'confidence' => 0.0
            ];
        }
    }

    /**
     * Get common medical terms in target language
     */
    public function getMedicalTerms(string $targetLanguage): array
    {
        $terms = [
            'en' => [
                'doctor' => 'Doctor',
                'patient' => 'Patient',
                'appointment' => 'Appointment',
                'prescription' => 'Prescription',
                'diagnosis' => 'Diagnosis',
                'treatment' => 'Treatment',
                'medicine' => 'Medicine',
                'clinic' => 'Clinic',
                'hospital' => 'Hospital',
                'emergency' => 'Emergency',
                'consultation' => 'Consultation',
                'specialization' => 'Specialization',
                'qualification' => 'Qualification',
                'registration' => 'Registration',
                'license' => 'License',
                'certificate' => 'Certificate'
            ]
        ];

        if ($targetLanguage === 'en' || !isset($terms[$targetLanguage])) {
            return $terms['en'];
        }

        // Translate medical terms
        $translatedTerms = [];
        foreach ($terms['en'] as $key => $term) {
            $result = $this->translate($term, $targetLanguage);
            $translatedTerms[$key] = $result['text'];
        }

        return $translatedTerms;
    }
}
