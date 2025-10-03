<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Annotations as OA;

class SocialAuthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/auth/social/google/redirect",
     *     summary="Redirect to Google OAuth",
     *     description="Redirects user to Google OAuth consent screen for authentication",
     *     tags={"Authentication "},
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="redirect_url", type="string", example="https://accounts.google.com/oauth/authorize?...", description="Google OAuth authorization URL")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unable to redirect to Google",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unable to redirect to Google. Please try again."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function redirectToGoogle()
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();
            
            return response()->json([
                'success' => true,
                'redirect_url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to redirect to Google. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/social/google/callback",
     *     summary="Handle Google OAuth callback",
     *     description="Processes Google OAuth callback and authenticates/registers user",
     *     tags={"Authentication "},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="Authorization code from Google",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="State parameter for CSRF protection",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="patient"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/..."),
     *                     @OA\Property(property="provider", type="string", example="google")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration and login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration and login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="patient"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/..."),
     *                     @OA\Property(property="provider", type="string", example="google")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Google authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Google authentication failed. Please try again."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            return $this->handleSocialUser($googleUser, 'google');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/social/facebook/redirect",
     *     summary="Redirect to Facebook OAuth",
     *     description="Redirects user to Facebook OAuth consent screen for authentication",
     *     tags={"Authentication "},
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="redirect_url", type="string", example="https://www.facebook.com/v18.0/dialog/oauth?...", description="Facebook OAuth authorization URL")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unable to redirect to Facebook",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unable to redirect to Facebook. Please try again."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function redirectToFacebook()
    {
        try {
            $url = Socialite::driver('facebook')
                ->stateless()
                ->redirect()
                ->getTargetUrl();
            
            return response()->json([
                'success' => true,
                'redirect_url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to redirect to Facebook. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/social/facebook/callback",
     *     summary="Handle Facebook OAuth callback",
     *     description="Processes Facebook OAuth callback and authenticates/registers user",
     *     tags={"Authentication "},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="Authorization code from Facebook",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="State parameter for CSRF protection",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="patient"),
     *                     @OA\Property(property="avatar", type="string", example="https://platform-lookaside.fbsbx.com/..."),
     *                     @OA\Property(property="provider", type="string", example="facebook")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration and login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration and login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="patient"),
     *                     @OA\Property(property="avatar", type="string", example="https://platform-lookaside.fbsbx.com/..."),
     *                     @OA\Property(property="provider", type="string", example="facebook")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Facebook authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Facebook authentication failed. Please try again."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function handleFacebookCallback(Request $request)
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();
            
            return $this->handleSocialUser($facebookUser, 'facebook');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Facebook authentication failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle social user authentication/registration
     *
     * @param object $socialUser
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleSocialUser($socialUser, $provider)
    {
        try {
            // Check if user exists by provider ID
            $existingUser = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($existingUser) {
                // User exists, log them in
                $token = $existingUser->createToken('auth-token')->plainTextToken;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => [
                            'id' => $existingUser->id,
                            'name' => $existingUser->name,
                            'email' => $existingUser->email,
                            'user_type' => $existingUser->user_type,
                            'avatar' => $existingUser->avatar,
                            'provider' => $existingUser->provider,
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer'
                    ]
                ]);
            }

            // Check if user exists by email
            $userByEmail = User::where('email', $socialUser->getEmail())->first();
            
            if ($userByEmail) {
                // Update existing user with provider information
                $userByEmail->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar() ?? $userByEmail->avatar,
                ]);

                $token = $userByEmail->createToken('auth-token')->plainTextToken;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => [
                            'id' => $userByEmail->id,
                            'name' => $userByEmail->name,
                            'email' => $userByEmail->email,
                            'user_type' => $userByEmail->user_type,
                            'avatar' => $userByEmail->avatar,
                            'provider' => $userByEmail->provider,
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer'
                    ]
                ]);
            }

            // Create new user
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(16)), // Random password for social users
                'user_type' => 'patient', // Default user type for social login
                'is_active' => true,
                'avatar' => $socialUser->getAvatar(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => now(), // Social users are considered verified
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Registration and login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'user_type' => $user->user_type,
                        'avatar' => $user->avatar,
                        'provider' => $user->provider,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/social/login-with-token",
     *     summary="Social login with access token",
     *     description="Authenticate using social provider access token (for mobile applications)",
     *     tags={"Authentication "},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"provider","access_token"},
     *             @OA\Property(property="provider", type="string", enum={"google","facebook"}, example="google", description="Social authentication provider"),
     *             @OA\Property(property="access_token", type="string", example="ya29.a0AfH6SMC...", description="Access token from the social provider")
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
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="patient"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/..."),
     *                     @OA\Property(property="provider", type="string", example="google")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration and login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration and login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="patient"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/..."),
     *                     @OA\Property(property="provider", type="string", example="google")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid access token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Google authentication failed. Invalid access token."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The provider field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication failed. Please try again."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function loginWithToken(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,facebook',
            'access_token' => 'required|string'
        ]);

        try {
            $provider = $request->provider;
            $accessToken = $request->access_token;

            // Get user data from provider using access token
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($accessToken);

            return $this->handleSocialUser($socialUser, $provider);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($provider) . ' authentication failed. Invalid access token.',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}