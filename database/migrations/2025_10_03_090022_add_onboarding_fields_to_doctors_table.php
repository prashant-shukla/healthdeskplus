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
        Schema::table('doctors', function (Blueprint $table) {
            // Onboarding progress tracking
            $table->integer('onboarding_step')->default(1)->after('is_available');
            $table->boolean('onboarding_completed')->default(false)->after('onboarding_step');
            
            // Additional professional info fields
            $table->string('profile_photo')->nullable()->after('bio');
            $table->json('documents')->nullable()->after('profile_photo'); // Store document paths
            
            // Clinic specific fields
            $table->string('clinic_name')->nullable()->after('documents');
            $table->text('clinic_address')->nullable()->after('clinic_name');
            $table->string('clinic_city')->nullable()->after('clinic_address');
            $table->string('clinic_state')->nullable()->after('clinic_city');
            $table->string('clinic_pincode')->nullable()->after('clinic_state');
            $table->string('clinic_phone')->nullable()->after('clinic_pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_step',
                'onboarding_completed',
                'profile_photo',
                'documents',
                'clinic_name',
                'clinic_address',
                'clinic_city',
                'clinic_state',
                'clinic_pincode',
                'clinic_phone'
            ]);
        });
    }
};
