<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('practice_id')->constrained()->onDelete('cascade');
            $table->string('record_number')->unique();
            $table->date('record_date');
            $table->enum('type', ['consultation', 'lab_report', 'imaging', 'surgery', 'vaccination', 'other'])->default('consultation');
            $table->text('title');
            $table->longText('content'); // Main content of the medical record
            $table->json('vital_signs')->nullable(); // Blood pressure, temperature, etc.
            $table->json('attachments')->nullable(); // File attachments
            $table->text('notes')->nullable();
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
