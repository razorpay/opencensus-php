<?php

date_default_timezone_set('Asia/Kolkata');

$invoice_data                   = $data['invoice'];
$invoice_expire_by              = $invoice_data['expire_by'];
$invoice_payments               = $invoice_data['payments'];
$is_invoice_partial_payment     = $invoice_data['partial_payment'] === true;
$invoice_status                 = $invoice_data['status'];

?>

<!doctype html>
<html>
<head>
    <title>Payment Link</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

    @if (isset($invoice_data))
        <meta property="og:title" content="Payment of Rs. {{amount_format_IN($invoice_data['amount'])}} requested by {{$invoice_data['merchant_label']}} for {{$invoice_data['description']}}">
        <meta property="og:image" content="{{isset($data['merchant']['image']) ?  $data['merchant']['image'] : 'https://razorpay.com/favicon.png'}}">
        <meta property="og:image:width" content="276px">
        <meta property="og:image:height" content="276px">
        <meta property="og:description" content="Click on this link to pay to {{$invoice_data['merchant_label']}}">
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

    <script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="initAnalytics()" async></script>

    <script>
        function initAnalytics() {
            analytics.init(['ga', 'hotjar'], window.location.hostname.indexOf('razorpay.com') < 0);
            analytics.track('ga', 'pageview');


            if (typeof window.hj === 'function') {
                window.hj('tagRecording', ['pl_hosted']);
            }
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
                    button = '<button class="btn-link showmore" onclick="toggleTrimDescription(false)"> Show More </button>';
                }
            }

            document.getElementById('payment-for').innerHTML = desc + button;
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
                                Payment Request from {{$invoice_data['merchant_label']}}
                            </div>
                            <div id="inv-details-main">
                                <div class="info" style="margin-top: 28px;">
                                    PAYMENT FOR
                                    <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                                </div>

                                @if($invoice_expire_by and $invoice_status !== 'paid')
                                    <div class="info">
                                        {{$invoice_status === 'expired' ? 'EXPIRED ON' : 'EXPIRES ON'}}
                                        <div class="val">
                                            {{epoch_format($invoice_expire_by)}}
                                        </div>
                                    </div>
                                @endif

                                <div class="info">
                                    <span id="pay-title">AMOUNT PAYABLE</span>
                                    <div class="val" id="display-pay-amt">
                                        ₹{{amount_format_IN($invoice_data['amount'])}}
                                    </div>

                                    <div class="info" id="partial-payment-info">
                                        <div class="val">
                                            <b>₹{{amount_format_IN($invoice_data['amount_due'])}}</b>
                                            <span class="light">Due</span>
                                        </div>
                                        <div class="val">
                                            <span> ₹{{amount_format_IN($invoice_data['amount_paid'])}}</span>
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
                                                        ₹{{amount_format_IN($item['amount'])}} Paid </b>on {{epoch_format($item['created_at'])}}
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
                                        <div id="merchant-name">{{$invoice_data['merchant_label']}}</div>
                                        <div id="merchant-desc">Invoice #{{$invoice_data['id']}}</div>
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
                                    Oops! This payment link was cancelled. Please contact {{$invoice_data['merchant_label']}} support in case you have any queries.
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
                </div>
            </div>
            <div id="footer">
                <div>
                    <img style="padding:3px 0" src="data:image/png;base64,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" />
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
                        <img src="{{$data['merchant']['image']}}" width="100%">
                    @endif
                </div>
                <div id="header-details">
                    @if (isset($data['merchant']))
                        <div id="merchant">
                            <div id="merchant-name">{{$invoice_data['merchant_label']}}</div>
                            <div id="merchant-desc">Invoice #{{$invoice_data['id']}}</div>
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

                        <div class="info">
                            <span id="pay-title">AMOUNT PAYABLE</span>
                            <div class="val" id="display-pay-amt">
                                ₹{{amount_format_IN($invoice_data['amount'])}}
                            </div>
                            <div class="info" id="partial-payment-info">
                                <div class="val">
                                    <b>₹{{amount_format_IN($invoice_data['amount_due'])}}</b>
                                    <span class="light">Due</span>
                                </div>
                                <div class="val">
                                    <span>₹{{amount_format_IN($invoice_data['amount_paid'])}}</span>
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

                        @if($invoice_expire_by and $invoice_status !== 'paid')
                            <div class="info">
                                {{$invoice_status === 'expired' ? 'EXPIRED ON' : 'EXPIRES ON'}}
                                <div class="val">{{epoch_format($invoice_expire_by)}} </div>
                            </div>
                        @endif
                        @if($invoice_data['customer_details']['customer_name'] or $invoice_data['customer_details']['customer_email'])
                            <div class="info">
                                ISSUED TO
                                @if($invoice_data['customer_details']['customer_name'])
                                    <div class="val">{{$invoice_data['customer_details']['customer_name']}}</div>
                                @endif
                                @if($invoice_data['customer_details']['customer_email'])
                                    <div class="val">{{$invoice_data['customer_details']['customer_email']}}</div>
                                @endif
                            </div>
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
                                                ₹{{amount_format_IN($item['amount'])}} Paid </b>on {{epoch_format($item['created_at'])}}
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
                            Oops! This payment link was cancelled. Please contact {{$invoice_data['merchant_label']}} support in case you have any queries.
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
                    <a href="https://www.razorpay.com/payment-links" target="_blank">razorpay.com/payment-links</a>
                    and get started instantly
                </div>
                <img id="fin-logo" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAeAAAAApCAMAAADamgpvAAACf1BMVEX///////////+ps8fl6fI+fb8hfsD19vfAw8789PfsyNTo6Ojv8PMdRKXQ0da/H2P67PHblq3o6vAnSqfgp7r/mxWNkqWvwNzltsbe4esnPpHq7vfAzuObr9KrsLz14OgnNoAqLmhta2vw1d/BAF5ZhsKYnqzEyd26yN4rPYn////KT37XiKOip7i1uMHQcJLU1+Xd3eDHQHZ8enuUnsf/28dRhr//FxZze5fH2eolLG6ura3NXYYpR55mdrLPZoz/yZPUepnEB2V4g62CiZ2Fkr+PlLK1vNfFxMTFMm8VL4SFn8ru8vbH0OD/8N//4N9UX5lVarFob57/dnZhZomPjY6gnp4uRpVDVpT/YgP/tbUyPH1CRHf/Qz1PVYX/rEVaV1dzjMP/kpP/piT/t2s+War/UFDq8Pf/ISAYDBA4MzT/AAD/mgAncLj///8ESon/UADo7/YAcr1Ck8o4j8gkg8IHRYIviMUAa7e9CV01dLj5/P7h6vMALnlVnc4AY7Pv9fknMnYAW7H0+PtOms0gbra+AVq/AFS/AE8AZrcATpUBTo96stdvrNUhOIsAAV7a4u3U3usiJ2cZU5BaotACWaXX5vKCttkoWJOz0OdnqNPO2ehnj8JHgbu9FF6ix+Gyw92Uvt0UbbUALqQAMHoABG5hpdIAL5lFms14nMoDVJkAQ5DJ0+SrvdgAIZLL3+5fisEBP6oELosAI4fBAEJGbqQFO58gP5kBHXd5k7oABHvB2OoATa4MUJD/DQ6Zr88ii8YATKAAA5GLqM8XXJ8AOI0Eg8QsZKOjttJXeKoAF4QVHWc/ebpqhK//RAL/jwIYOZKKn8EWJ3X/MQD/fAAs2AW2AAAAa3RSTlOjoJzJsPj+pr6luaqo/rb8qNCr/Mn+08/Br/6u/tnGrf7+3rP89M+6/f2X69bKwuCzse/Y0Lf+/t3A/sPn/ePj0Nv33dfW0sG58/7kzr2trevq5N3l0Mn18/6/9fHu7Ozi4NHx4fHn4/P166ccpLEAABgfSURBVHja7Jjta1JRHMf9uSdXSkMrs23hqLBBDwZz1YiSlgUFWvhiPWxJ21ov1Evec+VKSWa5ajCyJIqRK2qs4ZtqG1SsqEsv7osWFesf6vw83pqXa7M3dyP6vDj3y5dzvHI/nHtQwzIDhv/8w4BhdYPeioGhapDKS6pBY9GysiK2DoDOgoHS2BRoasTwu2n0+1mjCTQ0VwGo79RcW8LhqG3GQVeaV4RhnXcwAATO9AeH+oaC/WcCAICN3+MdDIVCg14vStZcZa/buhTjreVrwWDvrGPwhZYHybvb8nydnnTaKx9/f3C/YgQD4y/XeIJ9YYW+oAf1ekMRhYFBTyNoLWtNJZYidVz11rfzJRIfr3FXbnBcrDDC64odYNkFG1CwIsyocXwoxe9QViPVn6SBfhT7gYGxP+AdQLPrkAji9QNoCB65HP0zl0fKBRsdIi/W19fH6/mPHNdSuDr6iLtRwEov4nxdLVTQu3aVboYVwWA27TQpuAE7BKyssUKDC68uK2ALZndNh8/n66jBQjko3S7EAZX8eobC4T2otqR5Tzh8+lsE1RZhKeQB0BBcJrMIXheFhErwvrgsxuNxUSYx7inPy7w8xj0QaYcljjqwT/tRGDcf2gWg1+8XRfDuvb25bHa+K5fr6jq749e2PjKXdjrfnnIbXe2zd9pne1xG2pqPHuiZeYFMvTl5tDTV6rNZLBbbtA+ggt8+qje8mA9fv1LDi0HFaFhT8HCUkbhOSQ1HU9eLIYoh9f68SrAoCSJFfsrdk+NEJLKY5B7KhMoV7pO4KBQRGUrEsVoEYan5krZggO23t2zQR68iGDG7dx7upYrn53PZSVPJmuPcZHphYbIboGbu2DHn3Am6P8HdPTM7c2djkampGmDfumPitcUybZs4YAXt9zP1Gy7nx6VLaLhccWTAoy1YUfzsy6t8Pt8ZjXbiNT8+PDxOw6tWlWBBJIJABPkd90QmBFMLS9LoKJqREFrTSUSUZZHQSIuqkaUK8wl+Et5a1BZsXHXo5q39TboYVp/Bu3ecm2/LtbVl1wPTtultOp1ecJqMdCu3O9s/d5sB3Cc+UbuMnu8X3Gym4+SEzWKz2aZZoQaaglp+0XBEbTjkBy3BaBf9JrbFkslYrBC9F0vScHf8C4bYRZVggmQywkvuiZRBpDFuTCIZeXTsuZQhDElgI50nkb8jQyohKR+lIRjokzh4e83jW2sB9Pm7CgUrUMem3iw1nN1r/kmL+f+0UcZx3Of2w9bqRg8bA4akGIh8aSeGTLIQBjKdCyYzbjFxOmNiosZfeOIJP3S9S3v0SltMu7WmX9IrtS2Eo52UFlcqrEQsEOmAGWf8g/x87iC4ciSa6FN299zzeS7Pktd93p/38xC1rN5aAMALt5qZF249am9v33uHkObhRz09914+lOjlm4dV+QzkryrR56/rGuGPQZEbAP/2tdr+PEH4TUJ0AGuIHe68FCpkMvxSihMKmcISv5nnkzDQ3QDYk3Mi1V+TNPvrpJrLAPhX54Szmv7F6cllYuFwMZmQJ5y5XLYqCNX4xESwWAxOOj1OaPCqdseb1jl+gjYZDxWdwZgQnDgOe7T7RDCr5PD9nG4GM2av9/sm31gHOd1fk+PbMwMNXQxqeI9Dx6/qAMbht1XAn7aq2LrurEECr10kpPX2Wnv7u++eAZJQi+/d+/HDYTBZw+8vg3qrHm0YElhtK6CVOgL9pW4CY9MV6dNNlrtNLM275zPitpBZcW+4QuEkZ19xrzTW4IgzAn/BYIwmlCC2SJVWFXkyUVRyitMjULXFZI8zwWOPy0K5pvFJBYjnnBFonlzOAzdnzumEEXj04IMHH5TJNOXlAM8HcopHm+/RpikTcU6ITCi4uh5gYrH6m7x3m/w23RQ2PHfMBy4NnSOpZQ7BGjS8JjX2zAsQIjr7YBTl+99998r+nS6iWqyFwamptU/BVZ+DCgxmq4t54a1HyPf2dRBrNNNdGuCu85jBmMLz13R21pDA0HQS+PQU1gfscJe4Jde4w1XiklxmBjoFnu926bhoWZGVSIITJMoJnNpEyheVrJDOyRCJUa5Y5ChNACrKVYswTc7yUtYjR4KBYCQiK04lEJCdiiIHlQh8Hh45IEfgAeOKkktTMSCn0zgzoM6HKQp0FGeVCsEIrq7oSTRh/U1379793ttpJI0xIMYaGaxnLGsieDWYWDM8mvv6WAvRmLbYbKyRaLKJHXyphVGfO2xGYrR1AJiWUfPhiUIjYObCHUjh7xZeI5rFQoUGXSYXQaHRYzGt7+/13OvZQ698tO3F3rXFKwAXklhzWY02yaBXgU8H/Pl7pwPOSNsrs3lIZJ4WXOMHrlmJ5t0Ox0wj4KAMPyGWqBZjnMhTrSVkIaYAC1mO0WJuMi7SKqAWAs5IQhICWcpnPYGEIHLVgCxnYyIXS8tKgqsmRS6RFsRY1pkWilVOLMZVwIFYLB5Jx0SIynJRSIShE0lLlBeyKBqyDmDG2ORDwHebyiwhJ6M+m4psFKIMWx4lHf4hYujzWq2aLYPQULn/6lWo4Di7sw9SlZC+zha4g/iX2V5buaXXwPr74fPRBUye/0DV6Fdxndf+2J+a2r9zDjC+pAJ+6yzz+m0E/PBWK/N3aW/9ogKAh4cr53VdFlron/UAn67R+oAdrgMuNL3RLcyu5CVacB/k5+xhYW46n3I1Ao4H4Cckgtl0AhADYfgrRqp8WsZIQKCxeCANgBEyDqWzQZDdtFKlVATpDmTV8y8pLRdhAEYkUYLUTFDKYxjmSnHI/WwcFYLSdJCDALDNwgx4KwhLBBoBI7o+v8bXN2bUi5bHtBS2+jqMPqsJGTLGcp+BmNkjwE0dlrExC4IdLaMM4N3LEgKA/eZe1m+ENUZNzHP6gI+K8OWzQPXywuAgWCwQ4+Zba+ixLhGiZjBo9HDXcR2Hwrz405XKi2cuzUMSr1w/CfiNr/QtltYAcEM7FbC7Lm1uOML8tnsmSTPuVHfJXc9slLoH3PqAuWIsxh1lMBfI8iKMaoAljuOBaJaniWA8HpADCDiLvIMJMRaHxM7ChQtUYVKcg06CStk0xTCARcBZHuYnq/gtJIIC5eJZfLkI5RzXiANgXYeFgL/3s0QvaoWk1JLT2t8EAqwC9lqNpBcTSgNs6B0ZswDPFq/16pCBAODOfp8NOipgHzvqYw2kwUU3FuH7HzQTcuGTBbRYZ3DrewcA/9F+nZCzWIOhPfzwmnbgpVmsxStX5m+2Xp8HmZ6Hz0CnBP9zwFoR1gPsmJkLCQeuVH5zdty9zWfcjvCmOzXg3gwdnACcSg2kBsKxBBAWREnlm3e1UWF2fCAFLUyxiYU5UIL63ACO2bd5Pj/LS3n7QMo+wNHNb2BEKmWoMDcXArmAh9ltys26SgLdhs5AXuLz9tlMTORp3R6iGdcAXjI0lNLWAMC6DgsT2Go5mQamMavF5jMy2Df7YCf1HAAeIYS92mk1mw4Bj3hto/5RQiDLxyysv4WoEm0r95l6zWUE3Okz48egDxiL8Cf3YSP8yQVCXkWLtYCGmpwDN40HWtjteYiEe37cu32p+dC9dVV+AsDXmBsvVgDwZ2f/N8DubSnjHnevrGzM2B2hbvdcKGmfm7EnhfFGib4RrcEviclZ2kYElN/eWhdpyFHBSC1JQ/XNzdloxcHRwlZtOdVWiJZ4/rGDF1PL0d2oS6D5xUXAvVunoVotTDe31nlpdZZyq1vTIbq+TjnXgcQ/ng7TUDd8DMshWt/aDcOlQMPRirr6jWfFUXVYWv7eBXp6/ovtBaKQfii3vhYVcB9hSAdr9Y9YiAbYZ+3vBH1nzD6W6WiymgCwt4Ow5SFLCwKGL4glDYBPFuH9/XNM8wcLU4ODYLFg6YsLAPjRrWa1//BhDyB+GbbCw1hu0WKhQp+/3gunHQD4ZvP/BXjaleRKG6VCd6Z7e9xdyNjtIalknymJOoB3gFI0ySXy05VoGxbg7oojRGlotbYb3YEI7V5crFWgh1kdneum0sETALwrIkou7EjStmgFsjdaB2CHgMXH65Tf3AJf9wQA76YkabVOhdpiiD6Ff/XFVQC8jIDVNQBw4yH0kcPyj5hOOlHLmN/aN9Lpa2HQi/U39VuICthgIozJVmYZTaKvGg3GsTGTqb+Ms/1mBgAbGUh566hPk+iyjZwu0eCn0Ebfv8ic29+fGty/c4HAgcdlFTDueeHhzId77wJhQLx1sxVHXv8CAC/ebGVe+Gwez7JukH9Vgz/6hzUYDzm4zPxKAQ2TcDB/kN/YSNL6ysYmDcM+uBFwbfdBNCkk0qVobZajNDxXKVAE/MPOg50HNeD34MHuzs7OD09EyrWBYrctQoo+WQbSSxwN157yNLlE6dPlDCCsYX4+pfzjdR4mCzRceUrF3RSljzeplIFpdQBc2FrFS53SpSff7uw8aACM5vjQYXnxLNpw4ojaNzRiHRrxDpl6wWQZW/yQvADYwFo7entbyrZDwGOmXsNQp8Xst+Lszn4T09dpxK3RVb9XM1kj6ND1AWtFeB8PK8++fX8QEvgyxEnz+2tT7VNw4KH9T7re+n3v3stIeFndLl0Cvj8tQpfouyw85/gvXPQ01NC/eLf+16SiKM59/lCzaLYMaowsDHRrtZJlX+jrjMZGRd9gQd+oiPolx/tFSg0t2ZeeUdpDMKxlazFStzbNTZeP0Zo41yTXX9Q59+levl60IvuMXe653itjH889n3fOMRQOs1aEP2xsMBrdVufIiNPqNhob5ARzOW429Pzxq9d3Ap+mre4XeEE7ygRzQDDHzUZwukjfMJafSVut6Zmk0e3wxPLRyLTT4WCnueycJ8ZxMc8i+LUzmba6Q26HvwCGL5d0svnZmMfhi7nDsGN6NAfDfN7vcC9mgeCIjGBmw9aSwuprZhjCIKQdRN1/QFejqQFf1ZKj4K90UPcfJtoPBzZoO4BEkeD+Zu3h/mZwcdit0awFMlv61UwNYdSmD1r6mKQ5/KFZQxQJloLw3pVXnoIDf8OyA1lz8RuE4MaVjPinQDnpbDyOPhyHsEzqzqADrzM0kab2CVFlVeU52O56GXKj9GUlgAVwwOSWjGCeC3KREKhoZzjwyR6bG+0JWakHZ2Gd4xaNaY6LILhofs5onM5FueTISDLKcfl0YTbKc9FkOg1mJFnIw1ohx+UKhWja6kzm07koGPnIbL4QjAbT6VwEjHwhyeGA52AzvK+MYKI5IDrwQG+bVqcXoSOSA7epGUxT6U0t2g54MAJFZtLoa49CIqO2rc0Eakq8Bdo6ak3NurVtG+hu3TYQZrV6QiPAtrXM2jY1nGwRn5SVPbgOg/DT83sffAEHPr8KCd6xpLFEAKmtXZ31T+qLRRDW7ROTk5PDN1WGdkMrTXVcW02qksmidSSzn31nfkEx93lsbAQZDr8wf5YRbOCjwWgk5PwIKvrdp4A9NzFnXSIYkE1EORECn41nweQFbzzuFXhvPAEGx/PZRCIr8EIURgFf9yay84tWT3Iqm+X5aCLO4X6YJLKwhcMddBCycF7gBC9XQTCBKDkwQPkFHdxhErFNT8oSTK8vXdo6tV6NDkh0ak2NWgcn9RvUulKOGV5V68HQq3G3uEmnBl2GB/R6NGGuUeuVM1lSEH7wACvDbyENjSlLzGhRjSVR3IoVw2JWxRDLKBAMMhoTlTRZCZH5T3LRP/F749QvctGDUP8dj7H2jCuTyYzPjPjt44/CIJ8Gxwd9bgWC+RAmOj4+twcCE2NsieB40AsQBMFbghDku708j2to8sFgkMdJECBQG+c87AvG02woGb3A01VcEOh2NHBHecBR6PZWEqzvKCusbRptf38f/vR9OCrlmBmcIU90inNGnBHpLi8ZBF8r29LJ8ro4SgQrBOFniP1fLqHTosZCgo9TsmFANLXHO5HgdmZnV3ESMYGgBaV1suAD+AfVJIR9wQ5C+kXms9/n9/tZq8/8sKchZs+YnR6jnGAbb/OGWPoYbAwEAjFrieAhvvsnCEL38hDMJ5e5lRJcoaHKF3Svlqlp6auluIehtapAghWCMOV3/9NjK8ScdErMeBCi2r6R0M9MnSWOMbhrJ9MqOrAIdGFUWcuvBytE4N3QXvnralKMHRv/TOsHkL4I2V32Bch3eN7JVPRO3ha0dYtXNMsuzkxbRfguAMF/D1vq/jJ3wgfsx4uMUaPCog7coiEEU9K993rvDVS99I8EKwVhwJYv+1eKpaLN36CohDlpKAuftmxXtataz1KRlTi0ou70cP3kZHF4dBjw5g3wq6Cy6CW97I6Omt8QbH64sACJojsNVmB44a5rAVKXGXlXpWXKZuNDYi6a9Y+4lwhO8ba/Rze/7NP0xisBy/zlJDRoKfRnDMhiyqPaBMOgFIRLCUu0N9GU1umNmImeSk3Ndz7pnJ/HbFYn3NCqeLF+snjwzCGKM5LKUurJqqR4D/Rkra+kF/klROmK7vmBYNcd6MJ6mDHi09JCzyDkpt8hwZUfU4vtfsrofA8yGnPRZcRsb4fuD90HDOEoTiR7SFqlv9KStCCZ0rJ0uvxmNksd+VlhlQvBRGeiERlc2KSrJsNKfdHMpi8YhEFKo8Qqa6y30OVBDI2djVj478Sif2M80UpWHBrF1qxDG+sQq3dCLktJZVEfPgecSn13yPblr8hpGUtdlUS56a7iioYfD/Brdt0ddL30ya9oTJAbVKqTJ+TYp/pPMEj/WInP3to+kx4cWCo7DAz0yUr/1SeYYBDGzqwru8RuDdq4k6JlYWyybKQAL65vXc0YuoqgtYZBTCMYcg1THesMyn1Z128jsSJgdhv6oq8isSKU+qKVYzCKLJ8PRJZzLHO3wejKjLFueZs5lZf4S7FCnDEryHLB1G1kmCWjqamJSJDWGXyJUTwvV1gDANqMJT0V06VevLOrBaJI8OrzTxGl1juy6+JbBFaVVF2pqamp+dQ8oMuCFUPLDLRmzdwUK0uY1Zp5A4C9Mogqfvf1H77ZcH03YSq+2XD1iLxlVtmDX2Z6XI/uPsqEnWPjULyBwn/GzLoVjhJF4J+JoE8V5XnZoK9SF/tezvm8MA1DcXyvHvI04Qk9RAcSIUiUQYOlCpVeFKsEBBFxPUoPgijoeephIMwfR+8K4k3Qv8J/zJe5Watleqhj4nfdmnz3o2OfvTRp84pZN6VBYEbb9/CD2j5DCqQxabS7GTPwaxsUe1gvWMdfxT4VbHpdL6PHZw7j6YW/pUHAgDrq+nbC5PW1phAntx+5fvX2rVu3bl89clbFyDhy7cKFC9eOfB/Nnb8Q64x+R24Sq5+bdPnePU5NungzOqzfRfDbxbtYWnz5+GH1/tm5U48/P1p8vD9yQ5fgHW2mRqRGgNLOlJhMRPyCIsldmogJcK3O89K4MuUyS8T7T2w35/hePYl8z7zsohXgyqsXZ6LJ46b9Au7++P36piTS6XSaimh0z/1pzhJE8RHXdev2g8OC7hMHAa++xfDyU3eokgdLjz8tF2+iMz7g8rwzmtBBgjUAkchqr7yuyd81WU2mNqkmZxLAHLUj6TQCaqMGZiyd3OpYF6xwunPHB7w7+WyQ7yB9+GPAW3+7ExOyM2I9+d5OBgk7Injx5dypH/T56evlu/exNDZgn80wQ60pAaY4SRLjcp3VBp13vvRGc81hnScwA4+1xjsZYZmV2e5dxYC7h07WHgRKxQWUFLxVCb7IgbceDYYt4grWLyAbkgHAHeFlT4unT6Pz/MbogEvSKO6UAOB1kqTocIa1m6YOPTmV+VmMW40zIDJGa2m0dyXjBzikyyzwz70PgZrbYl4URIUtQlXMQ/GgIF5TZRtf2Cqw7xtbVYVtCXYBXn34Uatv1mo5NmDIM+cniTYwAVkaKg1qcpnLpxqd0wprchzFWKIvDXOtidvtTKPBQ0j83nuGP0jbVG1gxBXZpmgrWdk8tEy9mLOhyK79JrSBLCW70kef9rWxFmMD5s0KAKxTiLAdgxZeo8KUF8xQgHcoMIXYiIM3BqeZI4HOq/8VMFHBVK1XzLJpfbAyPJw3VVEJWyV5GyRHeJAtcXGwiV6tHvGN132xEx9ejw+YNUHajP/E0Eirk+D6+jXi0C5ro2BPgOdEc1k13trYDrcht0Vo7JyaCkK0oj8P0PA65Ap+Arz4nUYH3GX/9HNJeOnVNuVN6UCuvbJ/ASgh1FGlICcpJEkASQLyXLDFXvRToQSwpZQUfcCXTvxel/4O4MPpLR244Gd1zmTA7b/5n7lq0YC+Al5AEr5Sh8z/AAAAAElFTkSuQmCC" />
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
            var successNote = "You have successfully paid ₹ " + (amount/100).toFixed(2);

            if (!data.invoice.partial_payment) {
                successNote += '<div> Payment ID: ' + data.invoice.payment_id + ' </div>'
            }

            document.getElementById('scs-msg').innerHTML = successNote;

            document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2);
        } else {
            document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2) + '<span id="paid-tag">PAID</span></span>';
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
        }

        function closePayHist() {
            document.getElementById('hist-modal').className = '';
            hideOverlay('overlay-hist');

            window.ga('send', 'event', 'PL Hosted Page', 'Click - Close Payment History', undefined, data.invoice.payments.length);
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

            var options = {
                key: data.key_id,
                invoice_id: invoiceObj.id,
                amount: invoiceObj.amount,
                // parent: '#chkout-box',
                description: 'Invoice #' + invoiceObj.id,
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

            options.name = invoiceObj.merchant_label;

            if (merchant) {
                var color = merchant.brand_color || '#168AFA';
                options.theme.color = color;

                if (merchant.image) {
                    options.image = merchant.image;
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
