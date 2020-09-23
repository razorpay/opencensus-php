<script type="text/javascript">
    window.SHIELD_STAGE = "{{ env('SHIELD_STAGE') }}";
    window.cdnDashboardUrl = "{{ config('app.cdn_dashboard_url') }}";
    window.OAUTH_CLIENT_ID = "{{ env('MERCHANT_OAUTH_CLIENT_ID') }}";
    window.INVISIBLE_CAPTCHA_SITE_KEY = "{{ env('INVISIBLE_CAPTCHA_SITE_KEY') }}";
    window.CHECKBOX_CAPTCHA_SITE_KEY = "{{ env('CHECKBOX_CAPTCHA_SITE_KEY') }}";
</script>
