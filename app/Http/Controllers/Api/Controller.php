<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="HealthDesk Plus API",
 *     version="1.0.0",
 *     description="Comprehensive healthcare management system for doctors, patients, appointments, prescriptions, and medical records.",
 *     @OA\Contact(
 *         email="support@healthdeskplus.com",
 *         name="HealthDesk Plus Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.healthdeskplus.com/api",
 *     description="Production Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT Bearer token"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard and analytics endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Patients",
 *     description="Patient management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Appointments",
 *     description="Appointment scheduling and management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Prescriptions",
 *     description="Prescription management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Medical Records",
 *     description="Medical records and reports endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Doctors",
 *     description="Doctor profile management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Practices",
 *     description="Practice/clinic management endpoints"
 * )
 * 
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error occurred"),
 *     @OA\Property(property="errors", type="object", description="Validation errors if any")
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation errors"),
 *     @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
 * )
 * 
 * @OA\Schema(
 *     schema="UnauthorizedError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 * 
 * @OA\Schema(
 *     schema="NotFoundError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Resource not found")
 * )
 * 
 * @OA\Schema(
 *     schema="Patient",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="string", example="PAT-ABC12345"),
 *     @OA\Property(property="practice_id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com", nullable=true),
 *     @OA\Property(property="phone", type="string", example="+91-9876543210"),
 *     @OA\Property(property="alternate_phone", type="string", example="+91-9876543211", nullable=true),
 *     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-15"),
 *     @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male"),
 *     @OA\Property(property="blood_group", type="string", example="A+", nullable=true),
 *     @OA\Property(property="address", type="string", example="123 Main St, City", nullable=true),
 *     @OA\Property(property="city", type="string", example="Mumbai", nullable=true),
 *     @OA\Property(property="state", type="string", example="Maharashtra", nullable=true),
 *     @OA\Property(property="pincode", type="string", example="400001", nullable=true),
 *     @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe", nullable=true),
 *     @OA\Property(property="emergency_contact_phone", type="string", example="+91-9876543212", nullable=true),
 *     @OA\Property(property="medical_history", type="array", @OA\Items(type="string"), example={"Diabetes", "Hypertension"}, nullable=true),
 *     @OA\Property(property="allergies", type="array", @OA\Items(type="string"), example={"Penicillin", "Dust"}, nullable=true),
 *     @OA\Property(property="medications", type="array", @OA\Items(type="string"), example={"Metformin", "Lisinopril"}, nullable=true),
 *     @OA\Property(property="notes", type="string", example="Patient notes", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Appointment",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="doctor_id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=1),
 *     @OA\Property(property="practice_id", type="integer", example=1),
 *     @OA\Property(property="appointment_number", type="string", example="APT-2025001"),
 *     @OA\Property(property="appointment_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="appointment_time", type="string", example="10:00:00"),
 *     @OA\Property(property="type", type="string", enum={"consultation","follow_up","emergency","surgery","other"}, example="consultation"),
 *     @OA\Property(property="status", type="string", enum={"scheduled","confirmed","in_progress","completed","cancelled","no_show"}, example="scheduled"),
 *     @OA\Property(property="reason", type="string", example="Regular checkup", nullable=true),
 *     @OA\Property(property="notes", type="string", example="Appointment notes", nullable=true),
 *     @OA\Property(property="fee", type="number", format="decimal", example=500.00, nullable=true),
 *     @OA\Property(property="payment_status", type="boolean", example=false),
 *     @OA\Property(property="confirmed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="cancelled_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="cancellation_reason", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Prescription",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="doctor_id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=1),
 *     @OA\Property(property="appointment_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="practice_id", type="integer", example=1),
 *     @OA\Property(property="prescription_number", type="string", example="PRES-2025001"),
 *     @OA\Property(property="prescription_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="chief_complaint", type="string", example="Fever and cough", nullable=true),
 *     @OA\Property(property="diagnosis", type="string", example="Upper respiratory infection", nullable=true),
 *     @OA\Property(property="medicines", type="array", @OA\Items(
 *         type="object",
 *         @OA\Property(property="name", type="string", example="Paracetamol"),
 *         @OA\Property(property="dosage", type="string", example="500mg"),
 *         @OA\Property(property="frequency", type="string", example="3 times a day"),
 *         @OA\Property(property="duration", type="string", example="5 days"),
 *         @OA\Property(property="instructions", type="string", example="Take after meals")
 *     ), example={{"name":"Paracetamol","dosage":"500mg","frequency":"3 times a day","duration":"5 days","instructions":"Take after meals"}}),
 *     @OA\Property(property="instructions", type="string", example="Rest and drink plenty of fluids", nullable=true),
 *     @OA\Property(property="notes", type="string", example="Prescription notes", nullable=true),
 *     @OA\Property(property="follow_up_date", type="string", format="date", example="2025-01-22", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="MedicalRecord",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="doctor_id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=1),
 *     @OA\Property(property="appointment_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="practice_id", type="integer", example=1),
 *     @OA\Property(property="record_number", type="string", example="REC-2025001"),
 *     @OA\Property(property="record_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="type", type="string", enum={"consultation","lab_report","imaging","surgery","vaccination","other"}, example="consultation"),
 *     @OA\Property(property="title", type="string", example="Consultation Report"),
 *     @OA\Property(property="content", type="string", example="Detailed medical record content"),
 *     @OA\Property(property="vital_signs", type="object", 
 *         @OA\Property(property="blood_pressure", type="string", example="120/80"),
 *         @OA\Property(property="heart_rate", type="integer", example=72),
 *         @OA\Property(property="temperature", type="number", format="float", example=98.6),
 *         @OA\Property(property="weight", type="number", format="float", example=70.5),
 *         @OA\Property(property="height", type="number", format="float", example=175.0)
 *     , nullable=true),
 *     @OA\Property(property="attachments", type="array", @OA\Items(
 *         type="object",
 *         @OA\Property(property="filename", type="string", example="lab_report.pdf"),
 *         @OA\Property(property="url", type="string", example="/uploads/lab_report.pdf"),
 *         @OA\Property(property="type", type="string", example="application/pdf")
 *     ), nullable=true),
 *     @OA\Property(property="notes", type="string", example="Additional notes", nullable=true),
 *     @OA\Property(property="is_private", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Controller extends BaseController
{
    //
}
