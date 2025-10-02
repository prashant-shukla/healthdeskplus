<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Practice;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Register a new doctor",
     *     description="Register a new doctor with practice information",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","practice_name","practice_type","first_name","last_name","qualification","phone"},
     *             @OA\Property(property="name", type="string", example="Dr. John Doe", description="Full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Password confirmation"),
     *             @OA\Property(property="practice_name", type="string", example="City Medical Center", description="Practice/clinic name"),
     *             @OA\Property(property="practice_type", type="string", enum={"allopathy","homeopathy","ayurvedic"}, example="allopathy", description="Type of medical practice"),
     *             @OA\Property(property="first_name", type="string", example="John", description="First name"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Last name"),
     *             @OA\Property(property="qualification", type="string", example="MBBS, MD", description="Medical qualifications"),
     *             @OA\Property(property="specialization", type="string", example="General Medicine", description="Medical specialization", nullable=true),
     *             @OA\Property(property="phone", type="string", example="+91-9876543210", description="Phone number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Dr. John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="doctor")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'practice_name' => 'required|string|max:255',
            'practice_type' => 'required|in:allopathy,homeopathy,ayurvedic',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find existing practice or create new one
            $practice = Practice::where('name', $request->practice_name)
                ->where('type', $request->practice_type)
                ->first();

            if (!$practice) {
                // Create new practice with unique slug
                $baseSlug = \Str::slug($request->practice_name);
                $slug = $baseSlug;
                $counter = 1;
                
                while (Practice::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $practice = Practice::create([
                    'name' => $request->practice_name,
                    'slug' => $slug,
                    'type' => $request->practice_type,
                    'is_active' => true,
                ]);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => 'doctor',
                'is_active' => true,
            ]);

            // Create doctor profile
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'practice_id' => $practice->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'qualification' => $request->qualification,
                'specialization' => $request->specialization,
                'phone' => $request->phone,
                'is_available' => true,
            ]);

            // Assign role
            $user->assignRole('doctor');

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user->load('doctor.practice'),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Login user",
     *     description="Authenticate user and return access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Dr. John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="doctor")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 401);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('doctor.practice'),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout user",
     *     description="Revoke the current access token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/auth/profile",
     *     summary="Get user profile",
     *     description="Get the authenticated user's profile information",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="user_type", type="string", example="doctor")
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
    public function profile(Request $request)
    {
        $user = $request->user()->load('doctor.practice');

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string|max:1000',
            'specialization' => 'sometimes|string|max:255',
            'experience_years' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only(['name', 'email']));

            if ($user->doctor) {
                $user->doctor->update($request->only([
                    'phone', 'bio', 'specialization', 'experience_years'
                ]));
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user->load('doctor.practice')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
