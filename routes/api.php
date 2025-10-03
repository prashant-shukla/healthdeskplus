<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\PracticeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Documentation Route - This will be handled by L5-Swagger package

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Social authentication routes
    Route::prefix('social')->group(function () {
        // Google OAuth routes
        Route::get('google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
        Route::get('google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
        
        // Facebook OAuth routes
        Route::get('facebook/redirect', [SocialAuthController::class, 'redirectToFacebook']);
        Route::get('facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);
        
        // Mobile app social login with token
        Route::post('login-with-token', [SocialAuthController::class, 'loginWithToken']);
    });
    
    // Onboarding routes (public - for new doctor registration)
    Route::prefix('onboarding')->group(function () {
        Route::post('quick-register', [OnboardingController::class, 'quickRegister']);
    });
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });
    
    // Onboarding routes (protected - for authenticated doctors)
    Route::prefix('onboarding')->group(function () {
        Route::get('status', [OnboardingController::class, 'getOnboardingStatus']);
        Route::post('professional-info', [OnboardingController::class, 'updateProfessionalInfo']);
        Route::post('clinic-info', [OnboardingController::class, 'updateClinicInfo']);
        Route::post('upload-documents', [OnboardingController::class, 'uploadDocuments']);
        Route::post('complete', [OnboardingController::class, 'completeOnboarding']);
    });

    // Practice management
    Route::prefix('practices')->group(function () {
        Route::get('/', [PracticeController::class, 'index']);
        Route::get('/{practice}', [PracticeController::class, 'show']);
        Route::put('/{practice}', [PracticeController::class, 'update']);
    });

    // Doctor management
    Route::prefix('doctors')->group(function () {
        Route::get('/', [DoctorController::class, 'index']);
        Route::get('/{doctor}', [DoctorController::class, 'show']);
        Route::put('/{doctor}', [DoctorController::class, 'update']);
        Route::get('/{doctor}/appointments', [DoctorController::class, 'appointments']);
        Route::get('/{doctor}/patients', [DoctorController::class, 'patients']);
    });

    // Patient management
    Route::prefix('patients')->group(function () {
        Route::get('/', [PatientController::class, 'index']);
        Route::post('/', [PatientController::class, 'store']);
        Route::get('/{patient}', [PatientController::class, 'show']);
        Route::put('/{patient}', [PatientController::class, 'update']);
        Route::delete('/{patient}', [PatientController::class, 'destroy']);
        Route::get('/{patient}/appointments', [PatientController::class, 'appointments']);
        Route::get('/{patient}/prescriptions', [PatientController::class, 'prescriptions']);
        Route::get('/{patient}/medical-records', [PatientController::class, 'medicalRecords']);
    });

    // Appointment management
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{appointment}', [AppointmentController::class, 'show']);
        Route::put('/{appointment}', [AppointmentController::class, 'update']);
        Route::delete('/{appointment}', [AppointmentController::class, 'destroy']);
        Route::post('/{appointment}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('/{appointment}/complete', [AppointmentController::class, 'complete']);
    });

    // Prescription management
    Route::prefix('prescriptions')->group(function () {
        Route::get('/', [PrescriptionController::class, 'index']);
        Route::post('/', [PrescriptionController::class, 'store']);
        Route::get('/{prescription}', [PrescriptionController::class, 'show']);
        Route::put('/{prescription}', [PrescriptionController::class, 'update']);
        Route::delete('/{prescription}', [PrescriptionController::class, 'destroy']);
    });

    // Medical records management
    Route::prefix('medical-records')->group(function () {
        Route::get('/', [MedicalRecordController::class, 'index']);
        Route::post('/', [MedicalRecordController::class, 'store']);
        Route::get('/{medicalRecord}', [MedicalRecordController::class, 'show']);
        Route::put('/{medicalRecord}', [MedicalRecordController::class, 'update']);
        Route::delete('/{medicalRecord}', [MedicalRecordController::class, 'destroy']);
    });

    // Dashboard and analytics
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', function (Request $request) {
            $doctor = $request->user()->doctor;
            if (!$doctor) {
                return response()->json(['success' => false, 'message' => 'Doctor profile not found'], 404);
            }

            $stats = [
                'total_patients' => $doctor->practice->patients()->count(),
                'total_appointments' => $doctor->appointments()->count(),
                'today_appointments' => $doctor->appointments()->whereDate('appointment_date', today())->count(),
                'upcoming_appointments' => $doctor->appointments()->where('status', 'scheduled')->whereDate('appointment_date', '>=', today())->count(),
                'completed_appointments' => $doctor->appointments()->where('status', 'completed')->count(),
                'total_prescriptions' => $doctor->prescriptions()->count(),
                'total_medical_records' => $doctor->medicalRecords()->count(),
            ];

            return response()->json(['success' => true, 'data' => $stats]);
        });
    });
});
