<?php

namespace App\Services;

use App\Models\Website;

class ConversionGoalProfileService
{
    public function profiles(): array
    {
        return [
            'appointment_booking' => [
                'label' => 'Appointment booking',
                'primary_action' => 'book_appointment',
                'primary_action_label' => 'Book appointment',
                'cta_label' => 'booking CTA',
                'journey_label' => 'appointment path',
                'audience_label' => 'prospective patients',
                'default_target_audience' => 'People looking for the configured services in the target location',
                'default_business_model' => 'Appointment-based professional services',
                'secondary_conversion_goals' => ['phone_click', 'email_click', 'booking_platform_click', 'form_submit'],
                'conversion_labels' => [
                    'book_appointment' => 'Appointment booked',
                    'booking_click' => 'Booking CTA clicked',
                    'booking_platform_click' => 'Booking platform clicked',
                    'phone_click' => 'Phone link clicked',
                    'email_click' => 'Email link clicked',
                    'form_submit' => 'Appointment form submitted',
                ],
                'data_sources' => ['Google Search Console', 'anonymous conversion events', 'SEO audits'],
                'focus' => ['service pages', 'booking CTAs', 'booking platform clicks', 'phone and email clicks', 'appointment paths'],
            ],
            'lead_generation' => [
                'label' => 'Lead generation',
                'primary_action' => 'lead_submitted',
                'primary_action_label' => 'Submit lead',
                'cta_label' => 'lead CTA',
                'journey_label' => 'lead capture path',
                'audience_label' => 'prospective customers',
                'default_target_audience' => 'Prospective customers evaluating the offer',
                'default_business_model' => 'Lead generation',
                'secondary_conversion_goals' => ['phone_click', 'email_click', 'demo_requested', 'contact_form_submit'],
                'conversion_labels' => ['lead_submitted' => 'Lead submitted', 'phone_click' => 'Phone link clicked', 'email_click' => 'Email link clicked', 'demo_requested' => 'Demo requested', 'contact_form_submit' => 'Contact form submitted'],
                'data_sources' => ['Google Search Console', 'anonymous conversion events', 'CRM or form analytics', 'SEO audits'],
                'focus' => ['high-intent landing pages', 'lead CTAs', 'form completion', 'phone and email actions'],
            ],
            'saas_signup' => [
                'label' => 'SaaS signup',
                'primary_action' => 'business_signup',
                'primary_action_label' => 'Create business account',
                'cta_label' => 'signup CTA',
                'journey_label' => 'signup and onboarding path',
                'audience_label' => 'prospective business customers',
                'default_target_audience' => 'Businesses evaluating the SaaS product',
                'default_business_model' => 'SaaS',
                'secondary_conversion_goals' => ['trial_started', 'onboarding_completed', 'first_campaign_created'],
                'conversion_labels' => ['business_signup' => 'Business signup', 'trial_started' => 'Trial started', 'onboarding_completed' => 'Onboarding completed', 'first_campaign_created' => 'First campaign created'],
                'data_sources' => ['Google Search Console', 'anonymous conversion events', 'product analytics', 'onboarding data'],
                'focus' => ['signup pages', 'trial starts', 'onboarding completion', 'first product value'],
            ],
            'saas_signup_and_subscription' => [
                'label' => 'SaaS signup and subscription',
                'primary_action' => 'business_signup',
                'primary_action_label' => 'Create business account',
                'cta_label' => 'signup CTA',
                'journey_label' => 'signup, trial, onboarding, and subscription path',
                'audience_label' => 'prospective and active business customers',
                'default_target_audience' => 'SMEs evaluating and adopting the loyalty platform',
                'default_business_model' => 'SaaS subscription',
                'secondary_conversion_goals' => ['trial_started', 'onboarding_completed', 'first_campaign_created', 'first_reward_created', 'subscription_started', 'business_activity_retained'],
                'conversion_labels' => ['business_signup' => 'Business signup', 'trial_started' => '14-day trial started', 'onboarding_completed' => 'Onboarding completed', 'first_campaign_created' => 'First campaign created', 'first_reward_created' => 'First reward created', 'subscription_started' => 'Subscription started', 'business_activity_retained' => 'Active business retained'],
                'data_sources' => ['Google Search Console', 'anonymous conversion events', 'product analytics', 'onboarding data', 'subscription data', 'business activity data'],
                'focus' => ['business signups', '14-day trial activation', 'subscription conversion', 'onboarding', 'business activity', 'retention'],
            ],
            'trial_activation' => [
                'label' => 'Trial activation',
                'primary_action' => 'trial_started',
                'primary_action_label' => 'Start trial',
                'cta_label' => 'trial CTA',
                'journey_label' => 'trial activation path',
                'audience_label' => 'trial prospects and new users',
                'default_target_audience' => 'Prospects and new users evaluating product value',
                'default_business_model' => 'Product-led SaaS',
                'secondary_conversion_goals' => ['onboarding_completed', 'first_value_action'],
                'conversion_labels' => ['trial_started' => 'Trial started', 'onboarding_completed' => 'Onboarding completed', 'first_value_action' => 'First value action completed'],
                'data_sources' => ['anonymous conversion events', 'product analytics', 'onboarding data'],
                'focus' => ['trial starts', 'onboarding completion', 'time to first value'],
            ],
            'paid_subscription' => [
                'label' => 'Paid subscription',
                'primary_action' => 'subscription_started',
                'primary_action_label' => 'Start subscription',
                'cta_label' => 'subscription CTA',
                'journey_label' => 'paid conversion path',
                'audience_label' => 'activated users and buyers',
                'default_target_audience' => 'Activated users ready to purchase',
                'default_business_model' => 'Recurring subscription',
                'secondary_conversion_goals' => ['pricing_viewed', 'checkout_started', 'subscription_renewed'],
                'conversion_labels' => ['subscription_started' => 'Subscription started', 'pricing_viewed' => 'Pricing viewed', 'checkout_started' => 'Checkout started', 'subscription_renewed' => 'Subscription renewed'],
                'data_sources' => ['anonymous conversion events', 'product analytics', 'subscription data'],
                'focus' => ['pricing clarity', 'checkout starts', 'paid conversion', 'renewal'],
            ],
            'loyalty_retention' => [
                'label' => 'Loyalty and retention',
                'primary_action' => 'business_activity_retained',
                'primary_action_label' => 'Retain active customer',
                'cta_label' => 'engagement action',
                'journey_label' => 'activation and retention path',
                'audience_label' => 'active customers',
                'default_target_audience' => 'Active customers who should continue receiving value',
                'default_business_model' => 'Retention-led recurring revenue',
                'secondary_conversion_goals' => ['repeat_campaign_created', 'reward_redeemed', 'subscription_renewed'],
                'conversion_labels' => ['business_activity_retained' => 'Active customer retained', 'repeat_campaign_created' => 'Repeat campaign created', 'reward_redeemed' => 'Reward redeemed', 'subscription_renewed' => 'Subscription renewed'],
                'data_sources' => ['product analytics', 'business activity data', 'subscription data'],
                'focus' => ['repeat activity', 'reward usage', 'renewal', 'retention risk'],
            ],
            'ecommerce_purchase' => [
                'label' => 'Ecommerce purchase',
                'primary_action' => 'purchase_completed',
                'primary_action_label' => 'Complete purchase',
                'cta_label' => 'purchase CTA',
                'journey_label' => 'product and checkout path',
                'audience_label' => 'prospective buyers',
                'default_target_audience' => 'Shoppers evaluating products',
                'default_business_model' => 'Ecommerce',
                'secondary_conversion_goals' => ['product_viewed', 'add_to_cart', 'checkout_started'],
                'conversion_labels' => ['purchase_completed' => 'Purchase completed', 'product_viewed' => 'Product viewed', 'add_to_cart' => 'Added to cart', 'checkout_started' => 'Checkout started'],
                'data_sources' => ['Google Search Console', 'anonymous conversion events', 'commerce analytics'],
                'focus' => ['product discovery', 'add to cart', 'checkout completion', 'purchase conversion'],
            ],
            'custom' => [
                'label' => 'Custom goal',
                'primary_action' => 'custom_conversion',
                'primary_action_label' => 'Complete primary action',
                'cta_label' => 'primary CTA',
                'journey_label' => 'conversion path',
                'audience_label' => 'target audience',
                'default_target_audience' => null,
                'default_business_model' => null,
                'secondary_conversion_goals' => [],
                'conversion_labels' => ['custom_conversion' => 'Primary conversion'],
                'data_sources' => ['connected data sources', 'anonymous conversion events'],
                'focus' => ['the configured primary action', 'supporting conversion actions'],
            ],
        ];
    }

    public function forWebsite(Website $website): array
    {
        $key = $website->primary_conversion_goal ?: $this->defaultPrimaryGoal($website);
        $definition = $this->profiles()[$key] ?? $this->profiles()['custom'];
        $secondaryGoals = $website->secondary_conversion_goals ?: $definition['secondary_conversion_goals'];
        $conversionLabels = array_replace($definition['conversion_labels'], $website->conversion_labels ?: []);

        foreach ($secondaryGoals as $goalKey) {
            $conversionLabels[$goalKey] ??= ucfirst(str_replace('_', ' ', $goalKey));
        }

        return [
            'key' => $key,
            ...$definition,
            'secondary_conversion_goals' => $secondaryGoals,
            'conversion_labels' => $conversionLabels,
            'target_audience' => $website->target_audience ?: $definition['default_target_audience'],
            'business_model' => $website->business_model ?: $definition['default_business_model'],
            'status' => $website->status,
            'approval_required' => ['campaigns', 'messages', 'website_changes'],
        ];
    }

    public function defaultPrimaryGoal(Website $website): string
    {
        return in_array($website->type, ['osteopathy', 'auriculotherapy', 'sexology'], true)
            ? 'appointment_booking'
            : 'lead_generation';
    }

    public function labelsFor(string $profile): array
    {
        return ($this->profiles()[$profile] ?? $this->profiles()['custom'])['conversion_labels'];
    }
}
