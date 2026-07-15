<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            $table->string('type', 60)->default('other')->change();
            $table->string('primary_conversion_goal', 80)->nullable()->after('tracking_key');
            $table->json('secondary_conversion_goals')->nullable()->after('primary_conversion_goal');
            $table->text('target_audience')->nullable()->after('secondary_conversion_goals');
            $table->string('business_model')->nullable()->after('target_audience');
            $table->json('conversion_labels')->nullable()->after('business_model');
        });

        $appointmentLabels = json_encode([
            'book_appointment' => 'Appointment booked',
            'booking_click' => 'Booking CTA clicked',
            'booking_platform_click' => 'Booking platform clicked',
            'phone_click' => 'Phone link clicked',
            'email_click' => 'Email link clicked',
            'form_submit' => 'Appointment form submitted',
        ]);

        DB::table('websites')
            ->whereIn('type', ['osteopathy', 'auriculotherapy', 'sexology'])
            ->update([
                'primary_conversion_goal' => 'appointment_booking',
                'secondary_conversion_goals' => json_encode(['phone_click', 'email_click', 'booking_platform_click', 'form_submit']),
                'conversion_labels' => $appointmentLabels,
                'business_model' => 'Appointment-based professional services',
            ]);

        DB::table('websites')
            ->where(function ($query): void {
                $query->where('name', 'like', '%SME Loyalty%')->orWhere('url', 'like', '%sme%loyalty%');
            })
            ->update([
                'primary_conversion_goal' => 'saas_signup_and_subscription',
                'secondary_conversion_goals' => json_encode(['trial_started', 'onboarding_completed', 'first_campaign_created', 'first_reward_created', 'subscription_started', 'business_activity_retained']),
                'conversion_labels' => json_encode(['business_signup' => 'Business signup', 'trial_started' => '14-day trial started', 'onboarding_completed' => 'Onboarding completed', 'first_campaign_created' => 'First campaign created', 'first_reward_created' => 'First reward created', 'subscription_started' => 'Subscription started', 'business_activity_retained' => 'Active business retained']),
                'target_audience' => 'SMEs evaluating and adopting the loyalty platform',
                'business_model' => 'SaaS subscription',
            ]);

        DB::table('websites')->whereNull('primary_conversion_goal')->update(['primary_conversion_goal' => 'lead_generation']);
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            $table->dropColumn(['primary_conversion_goal', 'secondary_conversion_goals', 'target_audience', 'business_model', 'conversion_labels']);
        });
    }
};
