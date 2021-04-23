<?php

date_default_timezone_set('Asia/Kolkata');

$invoice_data                   = $data['invoice'];
$invoice_expire_by              = $invoice_data['expire_by'];
$invoice_payments               = $invoice_data['payments'];
$is_invoice_partial_payment     = $invoice_data['partial_payment'] === true;
$invoice_status                 = $invoice_data['status'];
$customer_details               = $invoice_data['customer_details'];
$custom_labels                  = $data['custom_labels'];
$view_preferences               = $data['view_preferences'];
?>

<!doctype html>
<html>
<head>
    <title>{{{ $invoice_data['merchant_label'] }}} - Payment Link</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="description" content="Payment of {{$invoice_data['currency']}} {{amount_format_IN($invoice_data['amount'])}} requested by {{{ $invoice_data['merchant_label'] }}} for {{{ $invoice_data['description'] }}}">
    @include('invoice.robot')

    @if (isset($invoice_data))
        <meta property="og:title" content="Payment of {{$invoice_data['currency']}} {{amount_format_IN($invoice_data['amount'])}} requested by {{{ $invoice_data['merchant_label'] }}} for {{{ $invoice_data['description'] }}}">
        <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://cdn.razorpay.com/static/assets/logo/rzp.png'}}">

        <meta property="og:image:width" content="276px">
        <meta property="og:image:height" content="276px">
        <meta property="og:description" content="Click on this link to pay to {{{ $invoice_data['merchant_label'] }}}">
    @endif

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,600" rel="stylesheet" type="text/css"></link>
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
    @if($invoice_status !== 'paid')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif

    @include('invoice.payment_link_stylesheet')
    @include('invoice.payment_link_analytics')

    <script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="initAnalytics()" async></script>

    <script>

        function noop() {}

        // Empty Interface for rzpQ
        window.rzpQ = {
            interaction: noop, // Track components
            initiated: noop, // User starts an activity
            dropped: noop, // User drops an activity
            success: noop, // Successfully completes activity
            failed: noop, // A failure occured
            push: noop, // Explicitly push as custom event to the queue
            now: function() {
                return window.rzpQ;
            },
            setUser:noop, // Set a user one time
            defineEventModifiers: noop, // Extends to set custom event properties
            // Any modifiers
            paymentLink: function() {
                return window.rzpQ;
            }
        };

        function initAnalytics() {
            if (!window.analytics || window.location.hostname.indexOf('razorpay.com') < 0){
                return;
            }

            analytics.init(
                ['ga', 'hotjar', 'lj', 'perf'],
                {
                    lj: data.is_test_mode
                    ? '96df432a283745908a06f711acd9e5eb' // 'feb51cc8168711ea8d71362b9e155667' Please add this key when data analytics issue fixed
                    : '96df432a283745908a06f711acd9e5eb'
                },
                false,
                undefined,
                false,
                {
                    pref: {
                        route: 'payment_link'
                    }
                }
            );

            analytics.track('ga', 'pageview');


            if (typeof window.hj === 'function') {
                window.hj('tagRecording', ['pl_hosted']);
            }

            if (analytics.createQ) {
                window.rzpQ = analytics.createQ({ pollFreq:500 });
            }

            window.rzpQ.defineEventModifiers({
                'paymentLink':[
                    {
                        propertyName:'event_type',
                        value:'paymentlinks'
                    },
                    {
                        propertyName:'event_group',
                        value:'paymentlink-hostedpage-events'
                    },
                    {
                        propertyName:'page_id',
                        value: data.invoice.id
                    }
                ],
            });
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
            var data = window.RZP_DATA.data,
            desc = data.invoice.description,
            charLimit, pseudoChar, button = '';

            if (checkIsDesktop()) {
                charLimit = 200;
                pseudoChar = 45;
            } else {
                charLimit = 125;
                pseudoChar = 35;
            }

            if (desc && toTrim) {
                var visLength = 0;

                desc =  desc.trim();
                var descLength = desc.length;

                var i = 0;
                for (; i < desc.length ; i++) {
                    if (desc[i] === '\n') {
                        visLength += pseudoChar;
                    } else {
                        visLength++;
                    }

                    if (visLength > charLimit) {
                        i = i - 1;
                        break;
                    }
                }

                desc= desc.substr(0, i + 1);
                desc =  desc.trim();

                if (desc.length < descLength) {
                    desc += '...';

                    button = document.createElement('button');
                    button.className ="btn-link showmore";
                    button.onclick = function() { toggleTrimDescription(false); }
                    button.innerText = "Show More";
                }

            }

            var div = document.createElement('div');
            div.textContent = desc;

            if (button) {
                div.appendChild(button);
            }

            document.getElementById('payment-for').innerHTML = "";
            document.getElementById('payment-for').appendChild(div);
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

                return data.invoice &&
                    data.invoice.callback_url &&
                    data.invoice.callback_method;
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
<div id="invoice-status-container" class={{$invoice_status}}>
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
                                    This payment link is created in <b>Test Mode</b>. Only test payments can be made for this.
                                  </span>
                        @endif
                        <div class="inv-details">
                            <div class="inv-for">
                                Payment Request from {{{ $invoice_data['merchant_label'] }}}
                            </div>
                            <div id="inv-details-main">
                                <div class="info" style="margin-top: 28px;">
                                    PAYMENT FOR
                                    <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                                </div>

                                @if(isset($invoice_data['receipt']))
                                    <div class="info">
                                        {{ $custom_labels['receipt_number'] ?? 'RECEIPT NO.' }}
                                        <div class="val">
                                            {{{ $invoice_data['receipt'] }}}
                                        </div>
                                    </div>
                                @endif

                                @if($invoice_expire_by and in_array($invoice_status, array('paid', 'partially_paid')) === false)
                                    <div class="info">
                                        @if($invoice_status === 'expired')
                                            EXPIRED ON
                                        @else
                                            {{isset($custom_labels['expire_by']) ? $custom_labels['expire_by'] : 'EXPIRES ON'}}
                                        @endif
                                        <div class="val">
                                            {{epoch_format($invoice_expire_by)}}
                                        </div>
                                    </div>
                                @endif

                                <div class="info">
                                    <span id="pay-title">
                                        @if(isset($custom_labels['amount']))
                                            {{$custom_labels['amount']}}
                                        @elseif(isset($invoice_data['first_payment_min_amount']))
                                            TOTAL AMOUNT OVERDUE
                                        @else
                                            AMOUNT PAYABLE
                                        @endif
                                    </span>
                                    <div class="val" id="display-pay-amt">
                                        {{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount'])}}
                                    </div>

                                    <div class="info" id="partial-payment-info">
                                        <div class="val">
                                            <b>{{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_due'])}}</b>
                                            <span class="light">Due</span>
                                        </div>
                                        <div class="val">
                                            <span> {{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_paid'])}}</span>
                                            <span class="light">Paid</span>
                                        </div>
                                    </div>
                                    <div class="line-strike"></div>

                                </div>
                                @if($is_invoice_partial_payment && count($invoice_payments))
                                    <button class="btn-link showhistory" onclick="showPayHist()"> Show Payment History </button>
                                    <div id="hist-modal">
                                        <div id="hist-close" onclick="closePayHist()"><b>✕</b></div>

                                        <div class="modal-title">
                                            Successful Payments
                                            <div class="modal-desc">
                                                {{count($invoice_payments)}} Payment{{(count($invoice_payments) > 1) ? 's' : ''}} made for this request
                                            </div>
                                        </div>

                                        @foreach ($invoice_payments as $key => $item)
                                            <div class="modal-col">
                                                <div class="row"><b style="color: #2e3345">
                                                        {{$invoice_data['currency_symbol']}} {{amount_format_IN($item['amount'])}} Paid </b>on {{epoch_format($item['created_at'])}}
                                                </div>
                                                <div class="row">Paid using <span style="text-transform: capitalize">{{$item['method']}}</span></div>
                                                <div class="row">Payment ID: {{$item['id']}}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
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

                    <div id="chkout-box" class={{(in_array($invoice_status, ['paid', 'expired', 'cancelled'], true) === true) ? 'short' : ''}}>
                        <div id="chkout-header">
                            <div id="header-logo" class={{isset($data['merchant']['image']) ? 'visible' : ''}}>
                                @if (isset($data['merchant']['image']))
                                    <img src="{{$data['merchant']['image']}}" width="100%">
                                @endif
                            </div>

                            <div id="header-details">
                                @if (isset($data['merchant']))
                                    <div id="merchant">
                                        <div id="merchant-name">{{{ $invoice_data['merchant_label'] }}}</div>
                                        @if(isset($data['checkout_options']['description']))
                                            <div id="merchant-desc">Invoice {{$data['checkout_options']['description']}}</div>
                                        @endif
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
                        @if($invoice_status === 'cancelled')
                            <div id="cancelled-invoice">
                                <div class="title" style='color:#f54443; font-size: 18px;'>Payment Link Cancelled</div>
                                <div class="desc">
                                    Oops! This payment link was cancelled. Please contact {{{ $invoice_data['merchant_label'] }}} support in case you have any queries.
                                </div>
                            </div>
                        @elseif($invoice_status === 'expired')
                            <div id="cancelled-invoice">
                                <div class="title" style='color:#f54443; font-size:18px'>Payment Link Expired</div>
                                <div class="desc">
                                    Oops! This payment link expired on {{epoch_format($invoice_expire_by)}}. Please contact {{{ $invoice_data['merchant_label'] }}} support in case you have any queries.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div id="footer">
                <div>
                    <img style="padding:3px 0" src="https://cdn.razorpay.com/static/assets/pay_methods_branding.png" />
                    <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" style="float: right;"/>
                </div>
                <div>
                    Want to create payment links for your business? Visit
                    <a class="external-link" onclick="handlePaymentLinkDocURL()" target="_blank" rel="noopener">razorpay.com/payment-links</a>
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
                        <img src="{{$data['merchant']['image']}}" width="100%">
                    @endif
                </div>
                <div id="header-details">
                    @if (isset($data['merchant']))
                        <div id="merchant">
                            <div id="merchant-name">{{{ $invoice_data['merchant_label'] }}}</div>
                            @if(isset($data['checkout_options']['description']))
                                <div id="merchant-desc">Invoice {{$data['checkout_options']['description']}}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <div id="inv-info-container">
                @if($data['is_test_mode'] === true)
                    <span class="testmode-warning">
                              This payment link is created in <b>Test Mode</b>. Only test payments can be made for this.
                            </span>
                @endif
                <div class="inv-details">
                    <div id="inv-details-main">
                        <div class="info">
                            PAYMENT FOR
                            <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                        </div>

                        @if(isset($invoice_data['receipt']))
                            <div class="info">
                                {{ $custom_labels['receipt_number'] ?? 'RECEIPT NO.' }}
                                <div class="val">
                                    {{{ $invoice_data['receipt'] }}}
                                </div>
                            </div>
                        @endif

                        <div class="info">
                            <span id="pay-title">
                                @if(isset($custom_labels['amount']))
                                    {{$custom_labels['amount']}}
                                @elseif(isset($invoice_data['first_payment_min_amount']))
                                    TOTAL AMOUNT OVERDUE
                                @else
                                    AMOUNT PAYABLE
                                @endif
                            </span>
                            <div class="val" id="display-pay-amt">
                                {{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount'])}}
                            </div>
                            <div class="info" id="partial-payment-info">
                                <div class="val">
                                    <b>{{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_due'])}}</b>
                                    <span class="light">Due</span>
                                </div>
                                <div class="val">
                                    <span>{{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_paid'])}}</span>
                                    <span class="light">Paid</span>
                                </div>
                            </div>
                            <div class="line-strike"></div>
                        </div>
                        @if($invoice_status === 'paid' && !$is_invoice_partial_payment)
                            <div class="info">
                                PAYMENT ID
                                <div class="val" style="text-transform:unset">{{$invoice_data['payment_id']}}</div>
                            </div>
                        @endif

                        @if($invoice_expire_by and in_array($invoice_status, array('paid', 'partially_paid')) === false)
                            <div class="info">
                                @if($invoice_status === 'expired')
                                    EXPIRED ON
                                @else
                                    {{isset($custom_labels['expire_by']) ? $custom_labels['expire_by'] : 'EXPIRES ON'}}
                                @endif
                                <div class="val">{{epoch_format($invoice_expire_by)}} </div>
                            </div>
                        @endif

                        @if(empty($view_preferences['hide_issued_to']) === true)
                            @if(isset($customer_details['customer_name']) or isset($customer_details['customer_email']))
                                <div class="info">
                                    ISSUED TO
                                    @if($customer_details['customer_name'])
                                        <div class="val">{{{ $customer_details['customer_name'] }}}</div>
                                    @endif
                                    @if($customer_details['customer_email'])
                                        <div class="val">{{{ $customer_details['customer_email'] }}}</div>
                                    @endif
                                </div>
                            @endif
                        @endif
                        @if($is_invoice_partial_payment and count($invoice_payments))
                            <button class="btn-link showhistory" onclick="showPayHist()"> Show Payment History </button>
                            <div id="hist-modal">
                                <div id="hist-close" onclick="closePayHist()"><b>✕</b></div>

                                <div class="modal-title">
                                    Successful Payments
                                    <div class="modal-desc">
                                        {{count($invoice_payments)}} Payment{{(count($invoice_payments) > 1) ? 's' : ''}} made for this request
                                    </div>
                                </div>

                                @foreach ($invoice_payments as $key => $item)
                                    <div class="modal-col">
                                        <div class="row"><b style="color: #2e3345">
                                                {{$invoice_data['currency_symbol']}} {{amount_format_IN($item['amount'])}} Paid </b>on {{epoch_format($item['created_at'])}}
                                        </div>
                                        <div class="row">Paid using <span style="text-transform: capitalize">{{$item['method']}}</span></div>
                                        <div class="row">Payment ID: {{$item['id']}}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @if($invoice_status === 'cancelled')
                    <div id="cancelled-invoice">
                        <div class="title" style='color:#f54443; font-size:18px'>Payment Link Cancelled</div>
                        <div class="desc">
                            Oops! This payment link was cancelled. Please contact {{{ $invoice_data['merchant_label'] }}} support in case you have any queries.
                        </div>
                    </div>
                @elseif($invoice_status === 'expired')
                    <div id="cancelled-invoice">
                        <div class="title" style='color:#f54443; font-size:18px'>Payment Link Expired</div>
                        <div class="desc">
                            Oops! This payment link expired on {{epoch_format($invoice_expire_by)}}. Please contact {{$invoice_data['merchant_label']}} support in case you have any queries.
                        </div>
                    </div>
                @endif
            </div>

            <div id="footer">
                <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" />
                <div>
                    Want to create payment links for your business? Visit
                    <a class="external-link" onclick="handlePaymentLinkDocURL()" rel="noopener" target="_blank">razorpay.com/payment-links</a>
                    and get started instantly
                </div>
                <img id="fin-logo" src="https://cdn.razorpay.com/static/assets/pay_methods_branding.png" />
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

    function fullPaid() {
        var amount = data.invoice.amount;
        document.getElementById('pay-title').innerHTML = 'AMOUNT PAID';

        if (checkIsDesktop()) {
            document.getElementById('scs-box').style.display = 'block';
            var successNote = "You have successfully paid " + data.invoice.currency_symbol + ' ' + (amount/100).toFixed(2);

            if (!data.invoice.partial_payment) {
                successNote += '<div> Paid Using: <b style="text-transform: capitalize">'+ data.invoice.payments[0].method +'</b> </div>'
                successNote += '<div> Payment ID: ' + data.invoice.payment_id + ' </div>'
            }

            document.getElementById('scs-msg').innerHTML = successNote;

            document.getElementById('display-pay-amt').innerHTML = '<span> ' + data.invoice.currency_symbol + ' ' + (amount/100).toFixed(2);
        } else {
            document.getElementById('display-pay-amt').innerHTML = '<span> ' + data.invoice.currency_symbol + ' ' + (amount/100).toFixed(2) + '<span id="paid-tag">PAID</span></span>';
        }
    }

    if (data.invoice.partial_payment && data.invoice.status !== 'paid' && data.invoice.amount_paid != 0) {
        document.getElementById('partial-payment-info').style.display = 'block';
    }

    // Invoice full paid
    if (data.invoice.amount_due === 0 && data.invoice.status === 'paid') {
        fullPaid();
    }
    // Invoice cancelled/expired
    else if (data.invoice.status === 'cancelled' || data.invoice.status === 'expired') {
        document.getElementById('cancelled-invoice').style.display = 'block';

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
@if ($is_invoice_partial_payment)
    <script>
        function showPayHist() {
            document.getElementById('hist-modal').className = 'show';
            showOverlay('overlay-hist');

            window.ga('send', 'event', 'PL Hosted Page', 'Click - Show Payment History', undefined, data.invoice.payments.length);

            pushToRzpQ('pl.payment.browse_history');
        }

        function closePayHist() {
            document.getElementById('hist-modal').className = '';
            hideOverlay('overlay-hist');

            window.ga('send', 'event', 'PL Hosted Page', 'Click - Close Payment History', undefined, data.invoice.payments.length);

            pushToRzpQ('pl.payment.close_history');
        }
    </script>
@endif
@if ($invoice_status !== 'paid' and ($invoice_status !== 'expired' and $invoice_status !== 'cancelled'))
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

            var invoiceObj = data.invoice;
            var merchant = data.merchant;
            var checkoutOptions = data.checkout_options;

            var options = {
                key: data.key_id,
                invoice_id: invoiceObj.id,
                amount: invoiceObj.amount,
                // parent: '#chkout-box',
                handler: function(response) {
                    if (globalScope.hasRedirect()) {

                        return globalScope.redirectToCallback(
                            data.invoice.callback_url,
                            data.invoice.callback_method,
                            response
                        );
                    }

                    if (window.ga && window.ga.length) {
                        var sessionTDiff = (new Date()).getTime() - window.t0;
                        var paymentSuccessAction = data.invoice.partial_payment ? 'Payment Successful - Partial' : 'Payment Successful';

                        window.ga('send', 'event', 'PL Hosted Page', paymentSuccessAction, 'Session Duration(s)' , Math.floor(sessionTDiff/1000), {
                            hitCallback: function() {
                                return location.reload(); // To display the latest payment id
                            }
                        });
                    } else {
                        return location.reload(); // To display the latest payment id
                    }
                },
                prefill: {
                    contact: invoiceObj.customer_details.customer_contact,
                    email: invoiceObj.customer_details.customer_email,
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

            if (checkoutOptions['description']) {
                options.description = checkoutOptions['description'];
            }

            options.name = invoiceObj.merchant_label;

            if (data.custom_labels) {
                options.min_amount_label = data.custom_labels.first_payment_min_amount;
            }

            if (merchant) {
                var color = merchant.brand_color || '#168AFA';
                options.theme.color = color;

                if (merchant.image) {
                    options.image = merchant.image;
                }

                if (merchant.image_frame === false) {
                    options.theme.image_frame = false;
                }

                if (merchant.image_padding === false) {
                    options.theme.image_padding = false;
                }
            }

            var razorpay;

            if (checkIsDesktop()) {
                options.parent = '#chkout-box';
                razorpay = window.razorpay = Razorpay(options);
            } else {
                document.getElementById('mob-payment-btn').addEventListener('click', function() {
                    razorpay = window.razorpay = Razorpay(options);
                    razorpay.open();

                    pushToRzpQ('pl.payment.proceed');
                });
            }
        }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>
@endif

<script>
    (function (globalScope) {
        var data = globalScope.data;

        if (globalScope.hasRedirect() && data.request_params.razorpay_payment_id) {
            return globalScope.redirectToCallback(
                data.invoice.callback_url,
                data.invoice.callback_method,
                data.request_params
            );
        }
    }(window.RZP_DATA = window.RZP_DATA || {}));
</script>
</body>
</html>
