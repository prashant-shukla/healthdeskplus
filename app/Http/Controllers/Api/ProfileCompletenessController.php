<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\ProfileCompletenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class ProfileCompletenessController extends Controller
{
    private $profileCompletenessService;

    public function __construct(ProfileCompletenessService $profileCompletenessService)
    {
        $this->profileCompletenessService = $profileCompletenessService;
    }

    /**
     * @OA\Get(
     *     path="/ai/profile/analyze",
     *     summary="Analyze profile completeness",
     *     description="Get AI-powered analysis of profile completeness with suggestions",
     *     tags={"Profile Completeness"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile analysis completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="completion_percentage", type="integer", example=75),
     *                 @OA\Property(property="basic_analysis", type="object",
     *                     @OA\Property(property="completion_percentage", type="integer", example=75),
     *                     @OA\Property(property="total_fields", type="integer", example=20),
     *                     @OA\Property(property="completed_fields", type="integer", example=15),
     *                     @OA\Property(property="missing_fields", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="critical_missing", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="important_missing", type="array", @OA\Items(type="object"))
     *                 ),
     *                 @OA\Property(property="ai_suggestions", type="object",
     *                     @OA\Property(property="priority_actions", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="engagement_suggestions", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="professional_development", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="impact_analysis", type="array", @OA\Items(type="string"))
     *                 ),
     *                 @OA\Property(property="priority_actions", type="array", @OA\Items(
     *                     @OA\Property(property="action", type="string", example="Complete your full name"),
     *                     @OA\Property(property="field", type="string", example="name"),
     *                     @OA\Property(property="priority", type="string", example="high"),
     *                     @OA\Property(property="estimated_time", type="string", example="2-5 minutes"),
     *                     @OA\Property(property="impact", type="string", example="Critical for profile completion")
     *                 )),
     *                 @OA\Property(property="estimated_time_to_complete", type="string", example="30 minutes"),
     *                 @OA\Property(property="impact_score", type="integer", example=85)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function analyze(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $analysis = $this->profileCompletenessService->analyzeProfileCompleteness($doctor);

            if (!$analysis['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $analysis['error'] ?? 'Analysis failed',
                    'completion_percentage' => $analysis['completion_percentage'] ?? 0
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ai/profile/suggestions",
     *     summary="Get profile optimization suggestions",
     *     description="Get specific suggestions for improving profile completeness",
     *     tags={"Profile Completeness"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Category of suggestions",
     *         required=false,
     *         @OA\Schema(type="string", enum={"completeness", "engagement", "professional", "all"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suggestions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="completeness", type="object",
     *                     @OA\Property(property="title", type="string", example="Profile Completeness"),
     *                     @OA\Property(property="description", type="string", example="Complete all required fields to increase patient trust"),
     *                     @OA\Property(property="tips", type="array", @OA\Items(type="string"))
     *                 ),
     *                 @OA\Property(property="engagement", type="object",
     *                     @OA\Property(property="title", type="string", example="Patient Engagement"),
     *                     @OA\Property(property="description", type="string", example="Optimize your profile for better patient interaction"),
     *                     @OA\Property(property="tips", type="array", @OA\Items(type="string"))
     *                 ),
     *                 @OA\Property(property="professional", type="object",
     *                     @OA\Property(property="title", type="string", example="Professional Development"),
     *                     @OA\Property(property="description", type="string", example="Enhance your professional credibility"),
     *                     @OA\Property(property="tips", type="array", @OA\Items(type="string"))
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getSuggestions(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $category = $request->query('category', 'all');
            $tips = $this->profileCompletenessService->getOptimizationTips($doctor);

            if ($category !== 'all' && isset($tips[$category])) {
                $tips = [$category => $tips[$category]];
            }

            return response()->json([
                'success' => true,
                'data' => $tips
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ai/profile/optimize",
     *     summary="Get personalized optimization recommendations",
     *     description="Get AI-powered personalized recommendations for profile optimization",
     *     tags={"Profile Completeness"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="focus_area", type="string", enum={"completeness", "engagement", "professional", "all"}, example="all", description="Area to focus optimization on"),
     *             @OA\Property(property="time_available", type="string", example="30 minutes", description="Time available for optimization"),
     *             @OA\Property(property="goals", type="array", @OA\Items(type="string"), example={"increase_patients", "build_trust", "professional_growth"}, description="Optimization goals")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization recommendations generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommendations", type="array", @OA\Items(
     *                     @OA\Property(property="action", type="string", example="Add a professional bio"),
     *                     @OA\Property(property="reason", type="string", example="Builds patient trust and credibility"),
     *                     @OA\Property(property="estimated_time", type="string", example="10 minutes"),
     *                     @OA\Property(property="impact", type="string", example="High"),
     *                     @OA\Property(property="category", type="string", example="engagement")
     *                 )),
     *                 @OA\Property(property="priority_order", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="estimated_total_time", type="string", example="45 minutes"),
     *                 @OA\Property(property="expected_improvement", type="string", example="Profile completeness will increase from 75% to 95%")
     *             )
     *         )
     *     )
     * )
     */
    public function optimize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'focus_area' => 'nullable|string|in:completeness,engagement,professional,all',
            'time_available' => 'nullable|string',
            'goals' => 'nullable|array',
            'goals.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found'
                ], 404);
            }

            $focusArea = $request->input('focus_area', 'all');
            $timeAvailable = $request->input('time_available', '30 minutes');
            $goals = $request->input('goals', ['increase_patients', 'build_trust']);

            // Get current analysis
            $analysis = $this->profileCompletenessService->analyzeProfileCompleteness($doctor);
            
            // Generate personalized recommendations
            $recommendations = $this->generatePersonalizedRecommendations($analysis, $focusArea, $timeAvailable, $goals);

            return response()->json([
                'success' => true,
                'data' => $recommendations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate optimization recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate personalized recommendations
     */
    private function generatePersonalizedRecommendations(array $analysis, string $focusArea, string $timeAvailable, array $goals): array
    {
        $recommendations = [];
        $priorityOrder = [];
        $estimatedTotalTime = 0;

        // Get priority actions from analysis
        $priorityActions = $analysis['priority_actions'] ?? [];
        $aiSuggestions = $analysis['ai_suggestions'] ?? [];

        // Filter based on focus area
        if ($focusArea !== 'all') {
            $priorityActions = array_filter($priorityActions, function ($action) use ($focusArea) {
                return $this->matchesFocusArea($action, $focusArea);
            });
        }

        // Generate recommendations
        foreach ($priorityActions as $action) {
            $recommendation = [
                'action' => $action['action'],
                'reason' => $this->getActionReason($action, $goals),
                'estimated_time' => $action['estimated_time'],
                'impact' => $this->getImpactLevel($action['priority']),
                'category' => $this->getActionCategory($action['field'])
            ];

            $recommendations[] = $recommendation;
            $priorityOrder[] = $action['action'];
            
            // Add to estimated time
            $time = $this->parseTimeToMinutes($action['estimated_time']);
            $estimatedTotalTime += $time;
        }

        // Add AI suggestions if available
        if (!empty($aiSuggestions['priority_actions'])) {
            foreach ($aiSuggestions['priority_actions'] as $suggestion) {
                $recommendation = [
                    'action' => $suggestion,
                    'reason' => 'AI-recommended optimization',
                    'estimated_time' => '5-10 minutes',
                    'impact' => 'Medium',
                    'category' => 'ai_suggestion'
                ];

                $recommendations[] = $recommendation;
                $priorityOrder[] = $suggestion;
                $estimatedTotalTime += 7; // Average of 5-10 minutes
            }
        }

        // Calculate expected improvement
        $currentCompletion = $analysis['completion_percentage'] ?? 0;
        $expectedImprovement = min(100, $currentCompletion + 20); // Assume 20% improvement

        return [
            'recommendations' => array_slice($recommendations, 0, 10), // Limit to top 10
            'priority_order' => array_slice($priorityOrder, 0, 10),
            'estimated_total_time' => $this->formatMinutesToTime($estimatedTotalTime),
            'expected_improvement' => "Profile completeness will increase from {$currentCompletion}% to {$expectedImprovement}%"
        ];
    }

    /**
     * Check if action matches focus area
     */
    private function matchesFocusArea(array $action, string $focusArea): bool
    {
        $field = $action['field'] ?? '';
        
        switch ($focusArea) {
            case 'completeness':
                return in_array($field, ['name', 'email', 'phone', 'specialization', 'qualification', 'registration_number', 'clinic_name', 'clinic_address']);
            case 'engagement':
                return in_array($field, ['profile_photo', 'bio', 'working_hours', 'consultation_fees']);
            case 'professional':
                return in_array($field, ['experience_years', 'documents', 'qualification', 'registration_number']);
            default:
                return true;
        }
    }

    /**
     * Get action reason based on goals
     */
    private function getActionReason(array $action, array $goals): string
    {
        $field = $action['field'] ?? '';
        $priority = $action['priority'] ?? 'medium';

        $reasons = [
            'name' => 'Builds patient trust and credibility',
            'email' => 'Essential for patient communication',
            'phone' => 'Enables direct patient contact',
            'specialization' => 'Helps patients find the right doctor',
            'qualification' => 'Establishes professional credibility',
            'registration_number' => 'Required for legal practice',
            'clinic_name' => 'Helps patients locate your practice',
            'clinic_address' => 'Essential for patient visits',
            'profile_photo' => 'Builds trust and personal connection',
            'bio' => 'Showcases your expertise and experience',
            'working_hours' => 'Improves patient scheduling',
            'consultation_fees' => 'Provides transparency to patients'
        ];

        $baseReason = $reasons[$field] ?? 'Improves profile completeness';
        
        if (in_array('increase_patients', $goals)) {
            $baseReason .= ' and attracts more patients';
        }
        if (in_array('build_trust', $goals)) {
            $baseReason .= ' and builds patient trust';
        }
        if (in_array('professional_growth', $goals)) {
            $baseReason .= ' and enhances professional image';
        }

        return $baseReason;
    }

    /**
     * Get impact level
     */
    private function getImpactLevel(string $priority): string
    {
        return match ($priority) {
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => 'Medium'
        };
    }

    /**
     * Get action category
     */
    private function getActionCategory(string $field): string
    {
        $categories = [
            'name' => 'completeness',
            'email' => 'completeness',
            'phone' => 'completeness',
            'specialization' => 'completeness',
            'qualification' => 'professional',
            'registration_number' => 'professional',
            'clinic_name' => 'completeness',
            'clinic_address' => 'completeness',
            'profile_photo' => 'engagement',
            'bio' => 'engagement',
            'working_hours' => 'engagement',
            'consultation_fees' => 'engagement'
        ];

        return $categories[$field] ?? 'completeness';
    }

    /**
     * Parse time string to minutes
     */
    private function parseTimeToMinutes(string $timeString): int
    {
        if (strpos($timeString, 'hour') !== false) {
            return (int) $timeString * 60;
        }
        
        return (int) $timeString;
    }

    /**
     * Format minutes to time string
     */
    private function formatMinutesToTime(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }
        
        $hours = round($minutes / 60, 1);
        return "{$hours} hours";
    }
}
