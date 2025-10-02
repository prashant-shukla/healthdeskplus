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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('practice_id')->constrained()->onDelete('cascade');
            $table->string('prescription_number')->unique();
            $table->date('prescription_date');
            $table->text('chief_complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->json('medicines'); // Store medicines as JSON with details
            $table->text('instructions')->nullable();
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
