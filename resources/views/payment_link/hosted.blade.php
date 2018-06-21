<?php

date_default_timezone_set('Asia/Kolkata');
$error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';

$payment_page_data               = $data['payment_link'];
$payment_page_expire_by          = $payment_page_data['expire_by'];
$payment_page_status             = $payment_page_data['status'];

?>


<!doctype html>
<html>
<head>
    <title>Payment Link</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

    @if (isset($payment_page_data))
        <meta property="og:title" content="Payment of Rs. {{amount_format_IN($payment_page_data['amount'])}} requested by {{$data['merchant']['name']}} for {{$payment_page_data['title']}}">
        <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
        <meta property="og:image:width" content="276px">
        <meta property="og:image:height" content="276px">
        <meta property="og:description" content="Click on this link to pay to {{$data['merchant']['name']}}">
    @endif

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,600" rel="stylesheet" type="text/css"></link>
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
    @include('invoice.payment_link_stylesheet')
    <style>
        #payment_id {
            display: none
        }

    </style>

    <script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="initAnalytics()" async></script>

    <script>
    function initAnalytics() {
        analytics.init(['ga', 'hotjar'], window.location.hostname.indexOf('razorpay.com') < 0);
        analytics.track('ga', 'pageview');
    }

    function checkIsDesktop() {
        var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
        return width > 853;
    }

    function cleanHTML() {
        // Show content according to width
        if (checkIsDesktop()) {
            document.getElementById('desktop-container').style.display = 'block';
            document.getElementById('invoice-status-container').removeChild(document.getElementById('mobile-container'));
        } else {
            document.getElementById('mobile-container').style.display = 'block';
            document.getElementById('invoice-status-container').removeChild(document.getElementById('desktop-container'));
        }
    }

    function toggleTrimDescription(toTrim) {
        var data = window.RZP_DATA.data;
        desc = data.payment_link.description;
        var charLimit, pseudoChar, button = '';

        if (checkIsDesktop()) {
            charLimit = 200;
            pseudoChar = 45;
        } else {
            charLimit = 125;
            pseudoChar = 35;
        }

        if (desc && (desc.length > charLimit)) {
            if (toTrim) {
                var newLines = 0;
                newLines = (desc.match(new RegExp("\n", "g")) || []).length;

                if (newLines) {
                    for(let i = 0; i < newLines; i++) {
                        if ((charLimit - i * pseudoChar) < 0.6 * charLimit) {
                            desc = desc.substr(0, charLimit - i*pseudoChar);
                            break;
                        }
                    }
                } else {
                    desc = desc.substr(0,charLimit);
                }

                desc =  desc.trim();
                desc += '...';
                button = '<button class="btn-link showmore" onclick="toggleTrimDescription(false)"> Show More </button'
            }
        }

        var ele = document.getElementById('payment-for');
        if (ele) {
            ele.innerHTML = desc + button;
        }
    }
    </script>

    <script>
        (function (globalScope) {

            var data = {!!utf8_json_encode($data)!!};

            function forEach (dict, cb) {

                dict = dict || {};

                if (typeof dict !== "object" || typeof cb !== "function") {

                    return dict;
                }

                var key, value;

                for (key in dict) {

                    if (!dict.hasOwnProperty(key)) {

                        continue;
                    }

                    value = dict[key];
                    cb.apply(value, [value, key, dict]);
                }

                return dict;
            }

            function parseQuery(qstr) {

                var query = {};

                var a = (qstr[0] === '?' ? qstr.substr(1) : qstr).split('&'), i, b;

                for (i = 0; i < a.length; i++) {

                    b = a[i].split('=');
                    query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
                }

                return query;
            }

            function createHiddenInput (key, value) {

                var input = document.createElement("input");

                input.type  = "hidden";
                input.name  = key;
                input.value = value;

                return input;
            }

            function hasRedirect () {

                return data.payment_link &&
                    data.payment_link.callback_url &&
                    data.payment_link.callback_method;
            }

            function redirectToCallback (callbackUrl,
                                         callbackMethod,
                                         requestParams) {

                document.body.className = ([document.body.className,
                    "paid",
                    "has-redirect"]).join(" ");

                var form   = document.createElement("form"),
                    method = callbackMethod.toUpperCase(),
                    input, key;

                form.method = method;
                form.action = callbackUrl;

                forEach(requestParams, function (value, key) {

                    form.appendChild(createHiddenInput(key, value));
                });

                var urlParamRegex = /^[^#]+\?([^#]+)/,
                    matches       = callbackUrl.match(urlParamRegex),
                    queryParams;

                if (method === "GET" && matches) {

                    queryParams = matches[1];

                    if (queryParams.length > 0) {

                        queryParams = parseQuery(queryParams);

                        forEach(queryParams, function (value, key) {

                            form.appendChild(createHiddenInput(key, value));
                        });
                    }
                }

                document.body.appendChild(form);

                form.submit();
            }

            globalScope.data               = data;
            globalScope.hasRedirect        = hasRedirect;
            globalScope.redirectToCallback = redirectToCallback;
        }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>
</head>

<body>
<div id="invoice-status-container" class={{$payment_page_status}}>
    <!-- Desktop Container -->
    <div id="desktop-container">
        <div>
            <svg class="bg-svg" width="1665px" height="665px" viewBox="0 0 1665 665" preserveAspectRatio="none">
                <polygon fill="#fafafa" points="40 50 1665 210 1665 346 220 545 -150 150"></polygon>
                <polygon fill="#f5f5f5" transform="translate(0, -40)" points="-40 215 1865 0 1965 450 1550 730 0 680"></polygon>
            </svg>
            <div id="payment-container">

                <div class="table-box" id="inv-info-par">
                    <div id="inv-info-box">
                        @if($data['is_test_mode'] === true)
                            <span class="testmode-warning">
                                This payment page is created in <b>Test Mode</b>. Only test payments can be made for this.
                            </span>
                        @endif
                        <div class="inv-details">
                            <div id="inv-details-main">
                                <div class="inv-for">
                                    {{$payment_page_data['title']}}
                                </div>
                                @if(isset($payment_page_data['description']))
                                    <div class="info" style="margin-top: 4px;">
                                        <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                                    </div>
                                @endif
                                @if($payment_page_expire_by and $payment_page_status === 'active')
                                    <div class="info">
                                        EXPIRES BY
                                        <div class="val">
                                            {{epoch_format($payment_page_expire_by)}}
                                        </div>
                                    </div>
                                @endif

                                <div class="info">
                                    <span id="pay-title">AMOUNT PAYABLE</span>
                                    <div class="val" id="display-pay-amt">
                                        ₹{{amount_format_IN($payment_page_data['amount'])}}
                                    </div>

                                    <div class="line-strike"></div>

                                </div>
                            </div>
                        </div>
                        <div class="footer">
                            Powered by
                            <img src="https://cdn.razorpay.com/logo.svg" />
                        </div>
                    </div>
                </div>
                <div class="table-box" id="chkout-par">
                    <div id="overlay"></div>

                    <div id="chkout-box" class={{$payment_page_status === 'inactive' ? 'short' : ''}}>
                        <div id="chkout-header">
                            <div id="header-logo" class={{isset($data['merchant']['image']) ? 'visible' : ''}}>
                                @if (isset($data['merchant']['image']))
                                    <img src={{$data['merchant']['image']}} width="100%">
                                @endif
                            </div>

                            <div id="header-details">
                                @if (isset($data['merchant']))
                                    <div id="merchant">
                                        <div id="merchant-name">{{$data['merchant']['name']}}</div>
                                        <div id="merchant-desc">#{{$payment_page_data['id']}}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div id="scs-box">
                            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACUAAAAlCAMAAADyQNAxAAAASFBMVEUAAADD+tvD+tvD+tvD+tvD+dvD+trD+dvE+9vE/NvG+9zD/+HH/+H////C+doewGBj2JOW6rojwmOo8MeL5rF/4qh03qBm2ZW6Mr7TAAAADnRSTlMA6JrxzLKRiHlOOiIUAmMEAH8AAADOSURBVDjLlZRZDoMwDERtSAgEMizd7n/TSi0qSerE8P6QniwM46GY4J01DDbW+UAyY8M44GYUnKlDTjfl0tDin3ZIpR4yfSw1KNFkk7RpA2oM+3YtarTfTTvU6T4fExpjvF9tz8CQWR/Y4UC+JG3zih1PrigtvwdHVpdgyegSDLEugQkHt0w6iGa9trUgcfRey7ytogRDFokmSbDkkGhPQYIjj0SbBQk++4+LJHHIM3GXMnE6X3pWT+dev6EL96jftt4TZztH76/rXaj36ht1cjrNdgCxBgAAAABJRU5ErkJggg==" />
                            <div style="font-weight: 600; font-size: 18px">Payment Completed</div>
                            <div id="scs-msg"style="color:#9b9b9b"></div>
                        </div>
                        <div id="cancelled-crack"></div>
                        @if($payment_page_status === 'inactive')
                            <div id="cancelled-invoice">
                                <div class="title" style='color:#f54443; font-size: 18px;'>Inactive Page</div>
                                <div class="desc">
                                    Oops! This payment page is currently Inactive. Please contact {{$data['merchant']['name']}} support in case you have any queries.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div id="footer">
                <div>
                    <img style="padding:3px 0" src="data:image/jpeg;base64,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" />
                    <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" style="float: right;"/>
                </div>
                <div>
                    Want to create payment links for your business? Visit
                    <a href="https://www.razorpay.com/payment-links" target="_blank">razorpay.com/payment-links</a>
                    and get started instantly
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Container -->
    <div id="mobile-container">
        <div id="overlay"></div>
        <div id="payment-container--mob">
            <div id="chkout-header">
                <div id="header-logo" class={{isset($data['merchant']['image']) ? 'visible' : ''}}>
                    @if (isset($data['merchant']['image']))
                        <img src={{$data['merchant']['image']}} width="100%">
                    @endif
                </div>
                <div id="header-details">
                    @if (isset($data['merchant']))
                        <div id="merchant">
                            <div id="merchant-name">{{$data['merchant']['name']}}</div>
                            <div id="merchant-desc">#{{$payment_page_data['id']}}</div>
                        </div>
                    @endif
                </div>
            </div>
            <div id="inv-info-container">
                @if($data['is_test_mode'] === true)
                    <span class="testmode-warning">
                        This payment page is created in <b>Test Mode</b>. Only test payments can be made for this.
                    </span>
                @endif
                <div class="inv-details">
                    <div id="inv-details-main">
                        <div class="inv-for">
                            {{$payment_page_data['title']}}
                        </div>
                        @if(isset($payment_page_data['description']))
                            <div class="info" style="margin-top: 4px;">
                                <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                            </div>
                        @endif

                        <div class="info">
                            <span id="pay-title">AMOUNT PAYABLE</span>
                            <div class="val" id="display-pay-amt">
                                ₹{{amount_format_IN($payment_page_data['amount'])}}
                            </div>
                            <div class="line-strike"></div>
                        </div>
                        <div class="info" id="payment_id">
                            PAYMENT ID
                            <div class="val" style="text-transform:unset"></div>
                        </div>

                        @if($payment_page_expire_by and $payment_page_status === 'active')
                            <div class="info">
                                EXPIRES BY
                                <div class="val">{{epoch_format($payment_page_expire_by)}} </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if($payment_page_status === 'inactive')
                    <div id="cancelled-invoice">
                        <div class="title" style='color:#f54443; font-size: 18px;'>Inactive Page</div>
                        <div class="desc">
                            Oops! This payment page is currently Inactive. Please contact {{$data['merchant']['name']}} support in case you have any queries.
                        </div>
                    </div>
                @endif
            </div>

            <div id="footer">
                <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" />
                <div>
                    Want to create payment links for your business? Visit
                    <a href="https://www.razorpay.com/payment-links" target="_blank">razorpay.com/payment-links</a>
                    and get started instantly
                </div>
                <img id="fin-logo" src="data:image/jpeg;base64,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" />
            </div>
            <button id="mob-payment-btn">
                PROCEED TO PAY
            </button>
        </div>
    </div>
</div>

<script>
    cleanHTML();
    window.t0 = (new Date()).getTime(); // initial time stamp

    var data = window.RZP_DATA.data;
    var color = data.merchant.brand_color || '#168AFA';
    document.getElementById('chkout-header').style['background-color'] = color;


    toggleTrimDescription(true);

    function fullPaid(respPaymentId) {
        var amount = data.payment_link.amount;
        document.getElementById('pay-title').innerHTML = 'AMOUNT PAID';

        if (checkIsDesktop()) {
            document.getElementById('scs-box').style.display = 'block';
            var successNote = "You have successfully paid ₹ " + (amount/100).toFixed(2);

            successNote += '<div> Payment ID: ' + respPaymentId + ' </div>'

            document.getElementById('scs-msg').innerHTML = successNote;

            document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2);
        } else {
            document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2) + '<span id="paid-tag">PAID</span></span>';
            document.getElementById('payment_id').style.display = 'block';
            document.querySelector('#payment_id .val').innerHTML = respPaymentId;
        }
    }

    // Payment page is inactive
    if (data.payment_link.status === 'inactive') {
        document.getElementById('cancelled-invoice').style.display = 'block';

        console.log('.....');
        if (checkIsDesktop()) {
            document.getElementById('cancelled-crack').style.display = 'block';
            document.getElementById('chkout-box').style.background = '#f5f5f5';
        } else {
            document.getElementById('inv-details-main').style.display = 'none';
        }
    }
</script>

<script>
    function showOverlay(clsToAdd) {
        var overlay = document.getElementById('overlay');
        overlay.style.opacity = 1;

        if (clsToAdd && overlay.className.indexOf(clsToAdd) === -1) {
            overlay.className += " " + clsToAdd;
        }
    }

    function hideOverlay(clsToRemove) {
        var overlay = document.getElementById('overlay');
        overlay.style.opacity = 0;


        if (clsToRemove && overlay.className.indexOf(clsToRemove) > -1) {
            overlay.className = overlay.className.replace(clsToRemove, '');
        }
    }
</script>
@if ($payment_page_status !== 'inactive')
    @if (isset($data['error']))
        <div id="failure" class="card">
            {!! $error_icon !!}
            <h2>Payment Failed</h2>
            <p>{{ $data['error']['description'] }}</p>
            <button id="button" onclick="razorpay.open()">Retry</button>
        </div>
    @endif
    <script>
        if (checkIsDesktop()) {
            document.getElementById('chkout-box').addEventListener('mouseover', showOverlay);
            document.getElementById('chkout-box').addEventListener('mouseout', hideOverlay);
        } else {
            var payBtn = document.getElementById('mob-payment-btn');
            payBtn.style['background-color'] = color;
            payBtn.style['display'] = 'block';
        }

        (function (globalScope) {
            var data = globalScope.data;

            var paymentPageObj = data.payment_link;
            var merchant = data.merchant;

            var options = {
                key: data.key_id,
                payment_link_id: paymentPageObj.id,
                amount: paymentPageObj.amount,
                // parent: '#chkout-box',
                description: '#' + paymentPageObj.id,
                handler: function(response) {
                    if (globalScope.hasRedirect()) {

                        return globalScope.redirectToCallback(
                            data.payment_link.callback_url,
                            data.payment_link.callback_method,
                            response
                        );
                    }

                    if (ga && ga.length) {
                        var sessionTDiff = (new Date()).getTime() - window.t0;
                        var paymentSuccessAction = 'Payment Successful';

                        ga('send', 'event', 'Payment Page Hosted', paymentSuccessAction, 'Session Duration(s)' , Math.floor(sessionTDiff/1000), {
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
                    close_button: false,
                },
                modal: {
                    confirm_close: true,
                    escape: false
                }
            };

            options.name = data.merchant.name;

            if (merchant) {
                var color = merchant.brand_color || '#168AFA';
                options.theme.color = color;

                if (merchant.image) {
                    options.image = merchant.image;
                }
            }

            var razorpay;
            if (!data.error) {
                if (checkIsDesktop()) {
                    options.parent = '#chkout-box';
                    razorpay = window.razorpay = Razorpay(options);
                } else {
                    document.getElementById('mob-payment-btn').addEventListener('click', function() {
                        razorpay = window.razorpay = Razorpay(options);
                        razorpay.open();
                    });
                }
            }

        }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>
@endif

<script>
    (function (globalScope) {
        var data = globalScope.data;

        if (globalScope.hasRedirect() && data.request_params.razorpay_payment_id) {
            return globalScope.redirectToCallback(
                data.payment_link.callback_url,
                data.payment_link.callback_method,
                data.request_params
            );
        }
    }(window.RZP_DATA = window.RZP_DATA || {}));
</script>
</body>
</html>
