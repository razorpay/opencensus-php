<?php

date_default_timezone_set('Asia/Kolkata');

$invoice_data                   = $data['invoice'];
$invoice_expire_by              = $invoice_data['expire_by'];
$invoice_payments               = $invoice_data['payments'];
$is_invoice_partial_payment     = $invoice_data['partial_payment'] === true;
$invoice_status                 = $invoice_data['status'];
$isExpired                      = ($invoice_status === 'expired' or (isset($invoice_expire_by) === true and $invoice_expire_by <= time() and $invoice_status === 'issued'));
$invoice_status                 = $isExpired ? 'expired' : $invoice_status;
$customer_details               = $invoice_data['customer_details'];
$checkout_options               = $data['options']['checkout'];
$hostedpage_options             = $data['options']['hosted_page'];
$isHostedCheckout               = $hostedpage_options['enable_embedded_checkout'];
$view_preferences               = $data['view_preferences'];

$reportEmailUrl = 'https://razorpay.com/support/payments/report-merchant/?e=' . base64_encode($invoice_data['id']) . '&s=' . base64_encode('hosted');
$showReportMailFlag = false;
if (isset($view_preferences['exempt_customer_flagging']) === true) {
    $showReportMailFlag = !$data['is_test_mode'] and !$view_preferences['exempt_customer_flagging'];
}
?>

    <!doctype html>
<html>
<head>
    <title>{{{ $invoice_data['merchant_label'] }}} - Payment Link - {{ $invoice_data['id'] }}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="description" content="Payment of {{$invoice_data['currency']}} {{amount_format_IN($invoice_data['amount'])}} requested by {{{ $invoice_data['merchant_label'] }}} for {{{ $invoice_data['description'] }}}">
    @include('invoice.robot')

    @if (isset($invoice_data))
        <meta property="og:title" content="Payment of {{$invoice_data['currency']}} {{amount_format_IN($invoice_data['amount'])}} requested by {{{ $invoice_data['merchant_label'] }}} for {{{ $invoice_data['description'] }}}">

        @if (isset($data['merchant']['image']))
            <meta property="og:image" content="{{$data['merchant']['image']}}">
            <meta property="og:image:width" content="276px">
            <meta property="og:image:height" content="276px">
        @endif

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
        @if($isHostedCheckout)
            <script src="https://cdn.razorpay.com/static/hosted/embedded-invoke.js"></script>
        @else
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        @endif
    @endif

    @include('invoice.payment_link_stylesheet')
    @include('invoice.payment_link_analytics')

    <script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="initAnalytics()" async></script>

    <script>
        // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign#Polyfill
        if (typeof Object.assign !== 'function') {
          // Must be writable: true, enumerable: false, configurable: true
          Object.defineProperty(Object, "assign", {
            value: function assign(target, varArgs) { // .length of function is 2
              'use strict';
              if (target === null || target === undefined) {
                throw new TypeError('Cannot convert undefined or null to object');
              }

              var to = Object(target);

              for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];

                if (nextSource !== null && nextSource !== undefined) {
                  for (var nextKey in nextSource) {
                    // Avoid bugs when hasOwnProperty is shadowed
                    if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                      to[nextKey] = nextSource[nextKey];
                    }
                  }
                }
              }
              return to;
            },
            writable: true,
            configurable: true
          });
        }

        function noop() {}
        // Empty Interface for rzpQ
        window.rzpQ = {
            interaction: noop, // Push event without postfixs
            push: noop, // Explicitly push as custom event to the queue
            now: function() {
                return window.rzpQ;
            },
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
                    lj: "{{ $data['lumberjack_key'] }}"
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
                        value: "{{ $invoice_data['id'] }}"
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
                                    {{ $hostedpage_options['label']['description'] }}
                                    <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                                </div>

                                @if(isset($invoice_data['receipt']))
                                    <div class="info">
                                        {{ $hostedpage_options['label']['receipt'] }}
                                        <div class="val">
                                            {{{ $invoice_data['receipt'] }}}
                                        </div>
                                    </div>
                                @endif

                                @if($invoice_expire_by and in_array($invoice_status, array('paid', 'partially_paid')) === false)
                                    <div class="info">
                                        @if($invoice_status === 'expired')
                                            {{ $hostedpage_options['label']['expired_on'] }}
                                        @else
                                            {{ $hostedpage_options['label']['expire_by'] }}
                                        @endif
                                        <div class="val">
                                            {{epoch_format($invoice_expire_by)}}
                                        </div>
                                    </div>
                                @endif

                                <div class="info">
                                    <span id="pay-title">
                                        {{$hostedpage_options['label']['amount_payable']}}
                                    </span>
                                    <div class="val" id="display-pay-amt">
                                        {{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount'])}}
                                    </div>

                                    <div class="info" id="partial-payment-info">
                                        <div class="val">
                                            <b>{{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_due'])}}</b>
                                            <span class="light">{{$hostedpage_options['label']['partial_amount_due']}}</span>
                                        </div>
                                        <div class="val">
                                            <span> {{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_paid'])}}</span>
                                            <span class="light">{{$hostedpage_options['label']['partial_amount_paid']}}</span>
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
                                                        {{$invoice_data['currency_symbol']}} {{amount_format_IN($item['amount'])}} {{$hostedpage_options['label']['partial_amount_paid']}} </b>on {{epoch_format($item['created_at'])}}
                                                </div>
                                                <div class="row">Paid using <span style="text-transform: capitalize">{{$item['method']}}</span></div>
                                                <div class="row">Payment ID: {{$item['id']}}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if($hostedpage_options['footer']['razorpay_branding'])
                            <div class="footer">
                                @if(empty($data['merchant']['support_email']) === false or empty($data['merchant']['support_mobile']) === false)
                                    <span>For any queries, please contact <b>{{$invoice_data['merchant_label']}}</b></span>
                                    <div>
                                        @if (empty($data['merchant']['support_mobile']) === false)
                                            <span>
                                                <img src="https://cdn.razorpay.com/static/assets/hostedpages/merchant_phone.svg" alt="phone">{{$data['merchant']['support_mobile']}}
                                            </span>
                                        @endif
                                        @if (empty($data['merchant']['support_email']) === false)
                                            <span>
                                                <img src="https://cdn.razorpay.com/static/assets/hostedpages/merchant_email.svg" alt="email">{{$data['merchant']['support_email']}}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
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
                                        <div id="merchant-name">
                                            @if(empty($checkout_options['name']) === false)
                                                {{{ $checkout_options['name'] }}}
                                            @else
                                                {{{ $invoice_data['merchant_label'] }}}
                                            @endif
                                        </div>
                                        @if(empty($checkout_options['description']) === false)
                                            <div id="merchant-desc">{{$checkout_options['description']}}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if($isHostedCheckout && $invoice_status === 'issued')
                            <button id="desk-payment-btn">PROCEED TO PAY</button>
                        @endif
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

            @if($hostedpage_options['footer']['security_branding'])
                <div id="footer">
                    <div>
                        <img style="padding:3px 0" src="data:image/png;base64,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" />
                        <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" style="float: right;"/>
                    </div>
                    <div>
                        Want to create payment links for your business? Visit
                        <a class="external-link" onclick="handlePaymentLinkDocURL()" target="_blank" rel="noopener">razorpay.com/payment-links</a>
                        and get started instantly
                        @if($showReportMailFlag)
                            <div class="report-cta">
                                Please report this Payment Link if you find it to be suspicious
                                <a href={{$reportEmailUrl}} target="_blank" rel="noopener">
                                    <img src="https://cdn.razorpay.com/static/assets/email/flag.png" width="13px" alt="report flag" />
                                    Report Payment Link
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
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
                            <div id="merchant-name">
                                @if(empty($checkout_options['name']) === false)
                                    {{{ $checkout_options['name'] }}}
                                @else
                                    {{{ $invoice_data['merchant_label'] }}}
                                @endif
                            </div>
                            @if(empty($checkout_options['description']) === false)
                                <div id="merchant-desc">{{$checkout_options['description']}}</div>
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
                            {{ $hostedpage_options['label']['description'] }}
                            <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                        </div>

                        @if(isset($invoice_data['receipt']))
                            <div class="info">
                                {{ $hostedpage_options['label']['receipt'] }}
                                <div class="val">
                                    {{{ $invoice_data['receipt'] }}}
                                </div>
                            </div>
                        @endif

                        <div class="info">
                            <span id="pay-title">
                                {{$hostedpage_options['label']['amount_payable']}}
                            </span>
                            <div class="val" id="display-pay-amt">
                                {{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount'])}}
                            </div>
                            <div class="info" id="partial-payment-info">
                                <div class="val">
                                    <b>{{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_due'])}}</b>
                                    <span class="light">{{$hostedpage_options['label']['partial_amount_due']}}</span>
                                </div>
                                <div class="val">
                                    <span>{{$invoice_data['currency_symbol']}} {{amount_format_IN($invoice_data['amount_paid'])}}</span>
                                    <span class="light">{{$hostedpage_options['label']['partial_amount_paid']}}</span>
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
                                    {{ $hostedpage_options['label']['expired_on'] }}
                                @else
                                    {{ $hostedpage_options['label']['expire_by'] }}
                                @endif
                                <div class="val">{{epoch_format($invoice_expire_by)}} </div>
                            </div>
                        @endif

                        @if($hostedpage_options['show_preferences']['issued_to'])
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
                                                {{$invoice_data['currency_symbol']}} {{amount_format_IN($item['amount'])}} {{$hostedpage_options['label']['partial_amount_paid']}} </b>on {{epoch_format($item['created_at'])}}
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

            @if($hostedpage_options['footer']['security_branding'])
                <div id="footer">
                    <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" />
                    <div>
                        Want to create payment links for your business? Visit
                        <a class="external-link" onclick="handlePaymentLinkDocURL()" rel="noopener" target="_blank">razorpay.com/payment-links</a>
                        and get started instantly
                    </div>
                    <img id="fin-logo" src="data:image/png;base64,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" />
                    @if($showReportMailFlag)
                        <div class="report-cta">
                            Please report this Payment Link if you find it to be suspicious
                            <a href={{$reportEmailUrl}} target="_blank" rel="noopener">
                                <img src="https://cdn.razorpay.com/static/assets/email/flag.png" width="13px" alt="report flag" />
                                Report Payment Link
                            </a>
                        </div>
                    @endif
                </div>
            @endif

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

    var curTimeStamp = Math.floor(new Date().getTime() / 1000);
    var isExpireByTSStale = data.invoice.expire_by && data.invoice.expire_by <= curTimeStamp;

    if (data.invoice.status === 'issued' && isExpireByTSStale) {
        data.invoice.status = 'expired';
    }

    toggleTrimDescription(true);


    // This fn. must be called only when status is partially paid  / issued
    function safeRefreshPageAfterInterval() {
        var PAGE_REFRESH_TIMER = 15 * 60 * 1000; // 15 min

        window.setTimeout(function() {
          window.location.reload();
        }, PAGE_REFRESH_TIMER);
    }


    // This fn. must be called only when status is non-expired. If the status is already expired, then intent to pay must never occur, hence no point of calling this fn in that scenario.
    function redirectIfPageExpiredBeforeIntentToPay() {
      var curTimeStamp = Math.floor(new Date().getTime() / 1000);
      var isPageOpenedBeyondExpireBy = data.invoice.max_expire_by && data.invoice.max_expire_by <= curTimeStamp;

      if (isPageOpenedBeyondExpireBy) {
        window.location.reload(); // Reload the page
      }

      return isPageOpenedBeyondExpireBy;
    }

    function fullPaid() {
        var $hostedpage_options = data.options.hosted_page;
        var amount = data.invoice.amount;

        document.getElementById('pay-title').innerHTML = $hostedpage_options.label.amount_paid;

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
    if (data.invoice.status === 'paid') {
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
        safeRefreshPageAfterInterval(); // Add check to run after every interval

        if (checkIsDesktop()) {
            document.getElementById('chkout-box').addEventListener('mouseover', showOverlay);
            document.getElementById('chkout-box').addEventListener('mouseout', hideOverlay);

            var payBtn = document.getElementById('desk-payment-btn');
            if (payBtn) {
                payBtn.style['background-color'] = color;
                payBtn.style['display'] = 'block';
            }
        } else {
            var payBtn = document.getElementById('mob-payment-btn');
            payBtn.style['background-color'] = color;
            payBtn.style['display'] = 'block';
        }

        (function (globalScope) {
            var data = globalScope.data;

            var invoiceObj = data.invoice;
            var merchant = data.merchant;
            var $checkout_options = data.options.checkout;
            var $hostedpage_options = data.options.hosted_page;
            var $isHostedCheckout = !!Number($hostedpage_options.enable_embedded_checkout);

            // : base options
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
                callback_url: location.href,
                // Options related to Rendering preferences

                prefill: $checkout_options.prefill,
                theme: $checkout_options.theme,
                features: $checkout_options.features,
                readonly: $checkout_options.readonly,
                method: $checkout_options.method,
                hidden: $checkout_options.hidden,
                modal: $checkout_options.modal,
                partial_payment: $checkout_options.partial_payment,
                config: $checkout_options.config

                // : Labels in checkout
                // name: $checkout_options.label.business_slug || invoiceObj.merchant_label,
                // min_amount_label:  $checkout_options.label.min_amount,
                // partial_payment_label:  $checkout_options.label.partial_payment
            };

            var modalCheckoutOptions = $checkout_options.modal;
            options.modal = Object.assign(modalCheckoutOptions, options.modal); // Shouldn't override base options

            if (!options.description && !Number($checkout_options.hidden.entity_id)) {
                // If description is not set, and entity id is not hidden, then entity id is shown.
                options.description = "#" + invoiceObj.id;
            } else {
                options.description = $checkout_options.description;
            }

            // set from Rendering preferences
            options.name = $checkout_options.name || invoiceObj.merchant_label; // Same used in dummy  checkout as well
            options.remember_customer = $checkout_options.remember_customer;
            options.min_amount_label = $checkout_options.first_payment_min_amount;

            // : hidden option
            options.hidden = $checkout_options.hidden; // Eg: email, contact

            // : theme options
            var themeFromCheckoutOptions = $checkout_options.theme;
            options.theme = Object.assign(themeFromCheckoutOptions, options.theme); // Shouldn't override base options


            // : prefill options
            options.prefill.contact = "";
            options.prefill.email = "";

            if (invoiceObj.customer_details.customer_contact) {
                options.prefill.contact = invoiceObj.customer_details.customer_contact;
            }
            if (invoiceObj.customer_details.customer_email) {
                options.prefill.email = invoiceObj.customer_details.customer_email;
            }

            if (options.prefill.contact || options.prefill.email || invoiceObj.customer_details.is_contact_or_email_present === true) {
                // options.order_id = invoiceObj.order_id;
                options.customer_id = invoiceObj.customer_details.id;
            }

            if (merchant) {
                var color = $checkout_options.theme.color || merchant.brand_color || '#168AFA';
                options.theme.color = color;

                options.theme.backdrop_color = $checkout_options.theme.backdrop_color;

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

            if ($isHostedCheckout) {
                var ele;

                if (checkIsDesktop()) {
                    ele = document.getElementById('desk-payment-btn')
                } else {
                    ele = document.getElementById('mob-payment-btn')
                }

                ele.addEventListener('click', function() {
                    // Check if valid expiry
                    var isPageExpiredBeforeIntentToPay = redirectIfPageExpiredBeforeIntentToPay();

                    if (isPageExpiredBeforeIntentToPay) {
                        return;
                    }

                    // Open hosted checkout
                    var hostedCheckoutURL = 'https://api.razorpay.com/v1/checkout/embedded';
                    window.invokeHostedCheckout(options, 'post', hostedCheckoutURL); // Redirects to Hosted checkout page, so handler not needed
                });

            } else {
                var razorpay;

                if (checkIsDesktop()) {
                    options.parent = '#chkout-box';
                    razorpay = window.razorpay = Razorpay(options);
                } else {

                    document.getElementById('mob-payment-btn').addEventListener('click', function() {
                        // Check if valid expiry
                        var isPageExpiredBeforeIntentToPay = redirectIfPageExpiredBeforeIntentToPay();

                        if (isPageExpiredBeforeIntentToPay) {
                            return;
                        }

                        // Open checkout
                        razorpay = window.razorpay = Razorpay(options);
                        razorpay.open();

                        pushToRzpQ('pl.payment.proceed');
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
                data.invoice.callback_url,
                data.invoice.callback_method,
                data.request_params
            );
        }
    }(window.RZP_DATA = window.RZP_DATA || {}));
</script>
</body>
</html>
