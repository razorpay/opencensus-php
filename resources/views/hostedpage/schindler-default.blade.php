<?php
    $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';

    $payment_page_data          = $data['payment_link'] ?? null;
    $is_test_mode               = $data['is_test_mode'] ?? false;
    $has_udf                    = (empty($udf_schema) === false);
    $max_mobile_width           = 853;
    $contact = [
        'phone' => '1800 209 5438',
        'email' => 'schindlerindia.in@schindler.com'
    ];

    $email_subject = 'Query for Payment Page Id: '. $payment_page_data['id'];
    $intro_note = 'Welcome to Schindler. Now, pay your Schindler service bill in 4 simple steps :';
    $instructions = array('Enter the details for the service you availed.', 'Choose the method of payment. ', 'Pay the amount. ', 'Receive online confirmation and get a confirmation email.');
    $end_note =  'In case of any doubts, please reach out to Schindler on';
?>


<!doctype html>
<html lang="en">
    <head>
        <title>Schindler Service Bill Payment</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
        <meta name="description" content="Schindler Gateway for Payments. Pay online for your Schindler service bill in 4 simple steps via Razorpay Payment Pages.">
        @include('hostedpage.partials.robot')

        @if (isset($payment_page_data))
            <meta property="og:title" content="Payment request by {{{ $data['merchant']['name'] }}} for {{{ $payment_page_data['title'] }}}">
            <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
            <meta property="og:image:width" content="276px">
            <meta property="og:image:height" content="276px">
            <meta property="og:description" content="Click on this link to pay to {{{ $data['merchant']['name'] }}}">
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

        <script src="https://cdn.razorpay.com/static/hosted/create-order.js"></script>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

        @include('hostedpage.styles.general')
        @include('hostedpage.styles.success_animation')
        @include('hostedpage.styles.form_theme')
        @include('hostedpage.styles.desktop')
        @include('hostedpage.styles.mobile')
        @include('hostedpage.specific.schindler-default.styles')
        @include('hostedpage.scripts.utils')
        @include('hostedpage.specific.helpers')

        @include('hostedpage.specific.schindler-default.scripts')
    </head>

    <body>
        <div id="hostedpage-container">
            <!-- Desktop Container -->
            <div id="desktop-container">
                <div class="merchant-display-image"></div>
                @include('hostedpage.partials.header')
                <div class  ="content">
                    @include('hostedpage.partials.description')
                    @include('hostedpage.partials.form')
                </div>
                @include('hostedpage.partials.footer')
            </div>

            <!-- Mobile Container -->
            <div id="mobile-container">
                <div class="merchant-display-image"></div>
                <div class="content">
                    @include('hostedpage.partials.header')
                    @include('hostedpage.partials.description')
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
