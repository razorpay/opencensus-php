<?php
    $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';

    $payment_page_data          = $data['payment_link'] ?? null;
    $is_test_mode               = $data['is_test_mode'] ?? false;
    $has_udf                    = (empty($udf_schema) === false);
    $max_mobile_width           = 853;

    $email_subject = 'Query for Payment Page Id: '. $payment_page_data['id'];

    $intro_note = 'Kerala and Karnataka have been hit by relentless rain for the last two weeks. Rains, floods and landslides have resulted in loss of life and extensive damage in these states.';
    $end_note = 'Your contribution can go a long way in rebuilding the lives of flood-affected people in these states.';
?>


<!doctype html>
<html lang="en">
    <head>
        <title>Swiggy - Kodagu Flood Relief Campaign</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta charset="utf-8">
        <meta name="robots" content="noindex">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        <meta name="description" content="Swiggy campaign for Contribution towards Kodagu Flood Relief via Razorpay Payment Pages.">
        @include('hostedpage.partials.robot')

        @if (isset($payment_page_data))
            <meta property="og:title" content="Swiggy Cares for Kerala and Karnataka Flood Relief">
            <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
            <meta property="og:image:width" content="276px">
            <meta property="og:image:height" content="276px">
            <meta property="og:description" content="Click on this link to donate to {{{ $data['merchant']['name'] }}}">
        @endif

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

        @if (isset($data['environment']))
            @if ($data['environment'] !== 'production')
                <script>
                    var Razorpay = {
                        config: {
                            api: "{{ config('app.url') }}/"
                        }
                    }
                </script>
            @endif
        @endif

        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

        @include('hostedpage.styles.general')
        @include('hostedpage.styles.success_animation')
        @include('hostedpage.styles.form_theme')
        @include('hostedpage.styles.desktop')
        @include('hostedpage.styles.mobile')
        @include('hostedpage.scripts.utils')
        @include('hostedpage.specific.helpers')

        @include('hostedpage.specific.swiggy_relief-default.scripts')
    </head>

    <body>
        <div id="hostedpage-container">
            <!-- Desktop Container -->
            <div id="desktop-container" class="no-display-image">
                @include('hostedpage.partials.header')
                <div class="content" style="min-height: 450px !important;">
                    @include('hostedpage.specific.swiggy_relief-default.description', ['fund_type' => 'karnataka'])
                    @include('hostedpage.partials.form')
                </div>
                @include('hostedpage.partials.footer')
            </div>

            <!-- Mobile Container -->
            <div id="mobile-container" class="no-display-image">
                <div class="content">
                    @include('hostedpage.partials.header')
                    @include('hostedpage.specific.swiggy_relief-default.description', ['fund_type' => 'karnataka'])
                    <a href="#form" class="btn btn--full" id="mobile-proceed-btn">PROCEED TO PAY</a>
                </div>
                @include('hostedpage.partials.form')
            </div>
        </div>
        <script src="https://cdn.razorpay.com/static/libs/jsoneditor.min.js"></script>
        <script>
            window.RZP.cleanHTML();

            var editor = window.RZP.initJSONEditor();

            window.RZP.addListeners_Validators();

            window.t0 = (new Date()).getTime(); // initial time stamp
        </script>
    </body>
</html>
