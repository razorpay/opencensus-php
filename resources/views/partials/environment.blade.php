<script type="text/javascript">
    window.LUMBERJACK_API_KEY = "{{ env('LJ_KEY') }}";
    window.LUMBERJACK_API_URL = "{{ env('LUMBERJACK_API_URL') }}";
    window.SEGMENT_API_KEY = "{{ env('SEGMENT_API_KEY') }}";
    window.WEBSITE_SEGMENT_API_KEY = "{{ env('WEBSITE_SEGMENT_API_KEY') }}";
    window.X_WEBSITE_SEGMENT_API_KEY = "{{ env('X_WEBSITE_SEGMENT_API_KEY') }}";
    window.SHIELD_STAGE = "{{ env('SHIELD_STAGE') }}";
    window.cdnDashboardUrl = "{{ config('app.cdn_dashboard_url') }}";
    window.cdnBaseUrl = "{{ config('app.cdn_base_url') }}";
    window.bankingServiceUrl = "{{ config('app.banking_service_url') }}";
    window.OAUTH_CLIENT_ID = "{{ env('MERCHANT_OAUTH_CLIENT_ID') }}";
    window.INVISIBLE_CAPTCHA_SITE_KEY = "{{ env('INVISIBLE_CAPTCHA_SITE_KEY') }}";
    window.CHECKBOX_CAPTCHA_SITE_KEY = "{{ env('CHECKBOX_CAPTCHA_SITE_KEY') }}";
    window.RECAPTCHA_V3_SITE_KEY = "{{ env('RECAPTCHA_V3_SITE_KEY') }}";
    window.STREAKS_REWARDS = "{{ env('STREAKS_REWARDS') }}";
    window.REFINER_PROJECT_ID = "{{ env('REFINER_PROJECT_ID') }}";
    window.EASY_ONBOARDING_URL = "{{ env('EASY_ONBOARDING_URL') }}";
    window.PP_ECOMMERCE_URL = "{{ env('PP_ECOMMERCE_URL') }}";
    window.BANK_DETAILS_URL = "{{ env('BANK_DETAILS_URL') }}";
    window.APP_NAME = "{{ env('APP_NAME') }}";
    window.LUMBERJACK_METRICS_API_URL = "{{ env('LUMBERJACK_METRICS_API_URL') }}";
    window.RAZORPAY_WEBSITE = "{{ config('app.rzp_website_url') }}";

    // Sentry related configs
    window.APP_ENV = "{!! env('APP_ENV') !!}";
    window.INSTANCE_TYPE = "{!! env('INSTANCE_TYPE') !!}";
    window.SENTRY_DSN = "{!! env('SENTRY_DSN') !!}";

    // Public API URL
    window.PUBLIC_API_URL = "{{ config('api.public_api_url') }}";
</script>
