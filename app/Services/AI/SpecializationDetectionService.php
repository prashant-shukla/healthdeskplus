<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SpecializationDetectionService
{
    private $openAIService;
    private $qualificationMapping;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->qualificationMapping = $this->getQualificationMapping();
    }

    /**
     * Detect specialization from qualification text with enhanced logic
     */
    public function detectSpecialization(string $qualification): array
    {
        // First try rule-based detection for speed
        $ruleBasedResult = $this->ruleBasedDetection($qualification);
        
        if ($ruleBasedResult['confidence'] >= 0.8) {
            return $ruleBasedResult;
        }

        // Fall back to AI detection for complex cases
        $aiResult = $this->openAIService->detectSpecialization($qualification);
        
        // Combine results if AI confidence is higher
        if ($aiResult['confidence'] > $ruleBasedResult['confidence']) {
            return $aiResult;
        }

        return $ruleBasedResult;
    }

    /**
     * Rule-based specialization detection
     */
    private function ruleBasedDetection(string $qualification): array
    {
        $qualification = strtoupper(trim($qualification));
        $extractedQualifications = [];
        $specialization = 'Unknown';
        $confidence = 0.3;

        // Allopathy qualifications
        $allopathyPatterns = [
            'MBBS', 'MD', 'MS', 'DM', 'MCH', 'DNB', 'MRCP', 'FRCS', 'MRCS',
            'DIPLOMA', 'DCH', 'DGO', 'DORTHO', 'DPSYCH', 'DPM', 'DLO', 'DMRD',
            'DMRT', 'DIPLOMA IN', 'BDS', 'MDS', 'BPT', 'MPT', 'BSC NURSING',
            'MSC NURSING', 'B.PHARM', 'M.PHARM', 'PHARM D'
        ];

        // Homeopathy qualifications
        $homeopathyPatterns = [
            'BHMS', 'DHMS', 'MD HOM', 'MD HOMEOPATHY', 'DIPLOMA IN HOMEOPATHY',
            'CCH', 'PGDH', 'HOMOEOPATHY', 'HOMEOPATHY'
        ];

        // Ayurveda qualifications
        $ayurvedaPatterns = [
            'BAMS', 'MD AYURVEDA', 'MS AYURVEDA', 'MD AYUR', 'MS AYUR',
            'DIPLOMA IN AYURVEDA', 'AYURVEDA', 'AYURVEDIC'
        ];

        // Unani qualifications
        $unaniPatterns = [
            'BUMS', 'MD UNANI', 'MS UNANI', 'DIPLOMA IN UNANI', 'UNANI'
        ];

        // Siddha qualifications
        $siddhaPatterns = [
            'BSMS', 'MD SIDDHA', 'MS SIDDHA', 'DIPLOMA IN SIDDHA', 'SIDDHA'
        ];

        // Check for Ayurveda first (to avoid BAMS being confused with BDS)
        foreach ($ayurvedaPatterns as $pattern) {
            if (strpos($qualification, $pattern) !== false) {
                $extractedQualifications[] = $pattern;
                $specialization = 'Ayurveda';
                $confidence = 0.9;
                break;
            }
        }

        // Check for Homeopathy
        if ($specialization === 'Unknown') {
            foreach ($homeopathyPatterns as $pattern) {
                if (strpos($qualification, $pattern) !== false) {
                    $extractedQualifications[] = $pattern;
                    $specialization = 'Homeopathy';
                    $confidence = 0.9;
                    break;
                }
            }
        }

        // Check for Unani
        if ($specialization === 'Unknown') {
            foreach ($unaniPatterns as $pattern) {
                if (strpos($qualification, $pattern) !== false) {
                    $extractedQualifications[] = $pattern;
                    $specialization = 'Unani';
                    $confidence = 0.9;
                    break;
                }
            }
        }

        // Check for Siddha
        if ($specialization === 'Unknown') {
            foreach ($siddhaPatterns as $pattern) {
                if (strpos($qualification, $pattern) !== false) {
                    $extractedQualifications[] = $pattern;
                    $specialization = 'Siddha';
                    $confidence = 0.9;
                    break;
                }
            }
        }

        // Check for Allopathy last (to avoid conflicts with other systems)
        if ($specialization === 'Unknown') {
            foreach ($allopathyPatterns as $pattern) {
                if (strpos($qualification, $pattern) !== false) {
                    $extractedQualifications[] = $pattern;
                    $specialization = 'Allopathy';
                    $confidence = 0.9;
                    break;
                }
            }
        }

        // If still unknown, try partial matches (more specific patterns)
        if ($specialization === 'Unknown') {
            if (strpos($qualification, 'MBBS') !== false || strpos($qualification, 'MD') !== false || strpos($qualification, 'MS') !== false) {
                $specialization = 'Allopathy';
                $confidence = 0.6;
            } elseif (strpos($qualification, 'BHMS') !== false || strpos($qualification, 'HOM') !== false) {
                $specialization = 'Homeopathy';
                $confidence = 0.6;
            } elseif (strpos($qualification, 'BAMS') !== false || strpos($qualification, 'AYUR') !== false) {
                $specialization = 'Ayurveda';
                $confidence = 0.6;
            } elseif (strpos($qualification, 'BUMS') !== false || strpos($qualification, 'UNANI') !== false) {
                $specialization = 'Unani';
                $confidence = 0.6;
            } elseif (strpos($qualification, 'BSMS') !== false || strpos($qualification, 'SIDDHA') !== false) {
                $specialization = 'Siddha';
                $confidence = 0.6;
            }
        }

        return [
            'specialization' => $specialization,
            'confidence' => $confidence,
            'extracted_qualifications' => $extractedQualifications,
            'suggested_specialization_text' => $specialization,
            'detection_method' => 'rule_based'
        ];
    }

    /**
     * Get detailed specialization information
     */
    public function getSpecializationDetails(string $specialization): array
    {
        $details = [
            'Allopathy' => [
                'name' => 'Allopathy',
                'full_name' => 'Allopathic Medicine',
                'description' => 'Modern Western medicine system',
                'common_qualifications' => ['MBBS', 'MD', 'MS', 'DM', 'MCH', 'DNB'],
                'council' => 'Medical Council of India (MCI)',
                'practice_type' => 'allopathy'
            ],
            'Homeopathy' => [
                'name' => 'Homeopathy',
                'full_name' => 'Homeopathic Medicine',
                'description' => 'Alternative medicine system based on natural remedies',
                'common_qualifications' => ['BHMS', 'DHMS', 'MD Homeopathy'],
                'council' => 'Central Council of Homeopathy (CCH)',
                'practice_type' => 'homeopathy'
            ],
            'Ayurveda' => [
                'name' => 'Ayurveda',
                'full_name' => 'Ayurvedic Medicine',
                'description' => 'Traditional Indian medicine system',
                'common_qualifications' => ['BAMS', 'MD Ayurveda', 'MS Ayurveda'],
                'council' => 'Central Council of Indian Medicine (CCIM)',
                'practice_type' => 'ayurvedic'
            ],
            'Unani' => [
                'name' => 'Unani',
                'full_name' => 'Unani Medicine',
                'description' => 'Traditional Islamic medicine system',
                'common_qualifications' => ['BUMS', 'MD Unani', 'MS Unani'],
                'council' => 'Central Council of Indian Medicine (CCIM)',
                'practice_type' => 'unani'
            ],
            'Siddha' => [
                'name' => 'Siddha',
                'full_name' => 'Siddha Medicine',
                'description' => 'Traditional Tamil medicine system',
                'common_qualifications' => ['BSMS', 'MD Siddha', 'MS Siddha'],
                'council' => 'Central Council of Indian Medicine (CCIM)',
                'practice_type' => 'siddha'
            ]
        ];

        return $details[$specialization] ?? [
            'name' => $specialization,
            'full_name' => $specialization,
            'description' => 'Medical specialization',
            'common_qualifications' => [],
            'council' => 'Unknown',
            'practice_type' => 'other'
        ];
    }

    /**
     * Suggest qualifications based on specialization
     */
    public function suggestQualifications(string $specialization): array
    {
        $suggestions = [
            'Allopathy' => [
                'primary' => ['MBBS', 'MD', 'MS'],
                'secondary' => ['DM', 'MCH', 'DNB', 'MRCP', 'FRCS'],
                'diploma' => ['DCH', 'DGO', 'DORTHO', 'DPSYCH', 'DPM']
            ],
            'Homeopathy' => [
                'primary' => ['BHMS', 'DHMS'],
                'secondary' => ['MD Homeopathy', 'CCH'],
                'diploma' => ['Diploma in Homeopathy']
            ],
            'Ayurveda' => [
                'primary' => ['BAMS'],
                'secondary' => ['MD Ayurveda', 'MS Ayurveda'],
                'diploma' => ['Diploma in Ayurveda']
            ],
            'Unani' => [
                'primary' => ['BUMS'],
                'secondary' => ['MD Unani', 'MS Unani'],
                'diploma' => ['Diploma in Unani']
            ],
            'Siddha' => [
                'primary' => ['BSMS'],
                'secondary' => ['MD Siddha', 'MS Siddha'],
                'diploma' => ['Diploma in Siddha']
            ]
        ];

        return $suggestions[$specialization] ?? [];
    }

    /**
     * Validate qualification against specialization
     */
    public function validateQualification(string $qualification, string $specialization): array
    {
        $details = $this->getSpecializationDetails($specialization);
        $qualification = strtoupper(trim($qualification));
        
        $isValid = false;
        $matchedQualification = null;

        foreach ($details['common_qualifications'] as $validQual) {
            if (strpos($qualification, strtoupper($validQual)) !== false) {
                $isValid = true;
                $matchedQualification = $validQual;
                break;
            }
        }

        return [
            'is_valid' => $isValid,
            'matched_qualification' => $matchedQualification,
            'suggested_qualifications' => $details['common_qualifications'],
            'confidence' => $isValid ? 0.9 : 0.3
        ];
    }

    /**
     * Get qualification mapping for reference
     */
    private function getQualificationMapping(): array
    {
        return Cache::remember('qualification_mapping', 3600, function () {
            return [
                'allopathy' => [
                    'MBBS' => 'Bachelor of Medicine and Bachelor of Surgery',
                    'MD' => 'Doctor of Medicine',
                    'MS' => 'Master of Surgery',
                    'DM' => 'Doctorate of Medicine',
                    'MCH' => 'Master of Chirurgiae',
                    'DNB' => 'Diplomate of National Board',
                    'MRCP' => 'Member of the Royal College of Physicians',
                    'FRCS' => 'Fellow of the Royal College of Surgeons',
                    'BDS' => 'Bachelor of Dental Surgery',
                    'MDS' => 'Master of Dental Surgery'
                ],
                'homeopathy' => [
                    'BHMS' => 'Bachelor of Homeopathic Medicine and Surgery',
                    'DHMS' => 'Diploma in Homeopathic Medicine and Surgery',
                    'MD Homeopathy' => 'Doctor of Medicine in Homeopathy',
                    'CCH' => 'Certificate Course in Homeopathy'
                ],
                'ayurveda' => [
                    'BAMS' => 'Bachelor of Ayurvedic Medicine and Surgery',
                    'MD Ayurveda' => 'Doctor of Medicine in Ayurveda',
                    'MS Ayurveda' => 'Master of Surgery in Ayurveda'
                ],
                'unani' => [
                    'BUMS' => 'Bachelor of Unani Medicine and Surgery',
                    'MD Unani' => 'Doctor of Medicine in Unani',
                    'MS Unani' => 'Master of Surgery in Unani'
                ],
                'siddha' => [
                    'BSMS' => 'Bachelor of Siddha Medicine and Surgery',
                    'MD Siddha' => 'Doctor of Medicine in Siddha',
                    'MS Siddha' => 'Master of Surgery in Siddha'
                ]
            ];
        });
    }

    /**
     * Get all supported specializations
     */
    public function getAllSpecializations(): array
    {
        return [
            'Allopathy' => $this->getSpecializationDetails('Allopathy'),
            'Homeopathy' => $this->getSpecializationDetails('Homeopathy'),
            'Ayurveda' => $this->getSpecializationDetails('Ayurveda'),
            'Unani' => $this->getSpecializationDetails('Unani'),
            'Siddha' => $this->getSpecializationDetails('Siddha')
        ];
    }
}
