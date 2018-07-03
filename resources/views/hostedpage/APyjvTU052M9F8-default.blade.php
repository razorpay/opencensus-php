<?php
    $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';

    $payment_page_data      = $data['payment_link'] ?? null;
    $is_test_mode           = $data['is_test_mode'] ?? false;
    $has_udf                = (empty($udf_schema) === false);
    $max_mobile_width       = 853;
?>


<!doctype html>
<html lang="en">
    <head>
        <title>Payment Page - {{$payment_page_data['title']}}</title>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        @if (isset($payment_page_data))
            <meta property="og:title" content="Payment request by {{$data['merchant']['name']}} for {{$payment_page_data['title']}}">
            <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
            <meta property="og:image:width" content="276px">
            <meta property="og:image:height" content="276px">
            <meta property="og:description" content="Click on this link to pay to {{$data['merchant']['name']}}">
        @endif

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

        @if (isset($data['environment']))
            @if ($data['environment'] !== 'production')
                <script>
                    var Razorpay = {
                        config: {
                            api: '/'
                        }
                    }
                </script>
            @endif
        @endif

        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

        @include('hostedpage.partials.styles.common')
        @include('hostedpage.partials.styles.form_theme')
        @include('hostedpage.partials.styles.desktop')
        @include('hostedpage.partials.styles.mobile')

        @include('hostedpage.partials.scripts')
    </head>

    <body>
        <div class="hostedpage-container">
            <!-- Desktop Container -->
            <div id="desktop-container">
                @include('hostedpage.partials.header')
                <div class="content">
                    @include('hostedpage.partials.description')
                    @include('hostedpage.partials.form')
                </div>
                @include('hostedpage.partials.footer')
            </div>

            <!-- Mobile Container -->
            <div id="mobile-container">
                <div class="content">
                    @include('hostedpage.partials.header')
                    <div>
                        @include('hostedpage.partials.description')
                        <button class="btn btn--full" id="mobile-proceed-btn" onclick="scrollToMobileForm()">PROCEED TO PAY</button>
                    </div>
                </div>
                @include('hostedpage.partials.form')
            </div>
        </div>
        @if ($has_udf === true)
            {{--<link rel="stylesheet" id="theme_stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">--}}
            <script src="https://cdn.jsdelivr.net/npm/{{'@'}}json-editor/json-editor/dist/jsoneditor.min.js"></script>
        @endif
        <script>
            cleanHTML();
            window.t0 = (new Date()).getTime(); // initial time stamp

            var data = window.RZP_DATA.data;
            var color = data.merchant.brand_color || '#168AFA';

            toggleTrimDescription(true);

            function fullPaid(respPaymentId) {
                if (!respPaymentId) {
                    return;
                }

                var amount = data.payment_link.amount;
                document.getElementById('pay-title').innerHTML = 'AMOUNT PAID';

                if (checkIsDesktop()) {
                    document.getElementById('scs-box').style.display = 'block';
                    var successNote = "You have successfully paid ₹ " + (amount/100).toFixed(2);

                    successNote += '<div> Payment ID: ' + respPaymentId + ' </div>';

                    document.getElementById('scs-msg').innerHTML = successNote;

                    document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2);
                } else {
                    document.getElementById('mob-payment-btn').style.display = 'none';
                }
            }
        </script>
        <script>
            function submitUdf(btn) {
                var errors = editor.validate();
                console.log('ERRORS...', errors);

                if (errors.length) {
                    alert("Errors in the form");
                    return;
                }

                var udfData = editor.getValue();

                var amountEl = document.getElementsByName('amount')[0];
                var amount = amountEl.value;

                checkoutStart(window.RZP_DATA = window.RZP_DATA || {}, Object.assign({}, udfData, {amount: amount}));
            }

        // UDF start
        JSONEditor.defaults.languages.en.error_required = "";
        var element = document.getElementById('udf_container');
        var editor = new JSONEditor(element, {
            form_name_root: "",
            no_additional_properties: true,
            disable_properties: true,
            disable_edit_json: true,
            disable_collapse: true,
            disable_array_reorder: true,
            disable_array_delete: true,
            disable_array_add: true,
            theme: "bootstrap3",
            schema: {!! $udf_schema !!}
        });

        document.getElementById('udf_submit_btn').addEventListener('click', submitUdf);
        // UDF end

            function checkoutStart(globalScope, udfData) {
                var data = globalScope.data;

                var paymentPageObj = data.payment_link;
                var merchant = data.merchant;

                // Checkout options
                var options = {
                    key: data.key_id,
                    payment_link_id: paymentPageObj.id,
                    amount: parseInt(udfData.amount * 100),
                    notes: udfData,
                    description: '#' + paymentPageObj.id,
                    handler: function(response) {
                        removeForm();

                        if (globalScope.hasRedirect()) {

                            return globalScope.redirectToCallback(
                                data.payment_link.callback_url,
                                data.payment_link.callback_method,
                                response
                            );
                        }

                        if (window.ga && window.ga.length) {
                            var sessionTDiff = (new Date()).getTime() - window.t0;
                            var paymentSuccessAction = 'Payment Successful';

                            window.ga('send', 'event', 'Payment Page Hosted', paymentSuccessAction, 'Session Duration(s)' , Math.floor(sessionTDiff/1000), {
                                hitCallback: function() {
                                    return fullPaid(response.razorpay_payment_id); // To display the latest payment id
                                }
                            });
                        } else {
                            return fullPaid(response.razorpay_payment_id); // To display the latest payment id
                        }
                    },
                    callback_url: location.href,
                    theme: {
                    },
                    modal: {
                        confirm_close: true,
                        escape: false
                    }
                };

                options.name = data.merchant.name;
                options.theme.color = merchant.brand_color || '#168AFA';
                options.currency = 'INR';

                options.image = merchant.image;

                var razorpay;
                razorpay = window.razorpay = Razorpay(options);
                razorpay.open();
            };
        </script>
    </body>
</html>
