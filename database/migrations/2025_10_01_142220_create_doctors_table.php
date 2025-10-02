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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('practice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('title')->nullable(); // Dr., Prof., etc.
            $table->string('specialization')->nullable();
            $table->string('qualification')->nullable(); // MBBS, MD, BHMS, BAMS, etc.
            $table->string('registration_number')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('bio')->nullable();
            $table->json('consultation_fees')->nullable(); // Different fees for different types
            $table->json('working_hours')->nullable(); // Store working hours for each day
            $table->integer('experience_years')->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('settings')->nullable(); // Doctor-specific settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
