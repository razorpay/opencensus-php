<!DOCTYPE html>
<html lang="en">
    <head>
        <title> Reward Terms - {{$data['reward']['name']}} </title>

        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

        <script>
            'use strict';

            function noop() {}

            // Empty Interface for rzpQ
            window.rzpQ = {
                interaction: noop, // Track components
                initiated: noop, // User starts an activity
                dropped: noop, // User drops an activity
                success: noop, // Successfully completes activity
                failed: noop, // A failure occured
                viewed: noop,
                push: noop, // Explicitly push as custom event to the queue
                now: function() {
                    return window.rzpQ;
                },
                setUser:noop, // Set a user one time
                defineEventModifiers: noop, // Extends to set custom event properties
                merchantActions: function() {
                    return window.rzpQ;
                },
            };

            (function (global) {

                function initAnalytics() {
                    const environment = window.location.hostname.indexOf('razorpay.com') < 0 ? 'dev' : 'prod';

                    analytics.init(
                        ['lj'],
                        {
                            lj: "{{env('LUMBERJACK_STATIC_KEY')}}" // "96df432a283745908a06f711acd9e5eb"
                        },
                        false,
                        environment,
                        false,
                        { appName: 'pg-dashboard' }
                    );

                    if(analytics.createQ){
                        window.rzpQ = analytics.createQ({ pollFreq:500 });
                    }

                    window.rzpQ.defineEventModifiers({
                        'merchantActions':[
                            {
                                propertyName:'event_type',
                                value:'email-events'
                            },
                            {
                                propertyName:'mode',
                                value:'live'
                            }
                        ]
                    });

                    const urlArr = window.location.href.split("/");
                    const paymentId = urlArr[urlArr.length-2];

                    window.rzpQ.push(
                        window.rzpQ.now().merchantActions().success(
                            'reward_terms_page.visited',
                            {
                                payment_id: paymentId,
                                reward_id: "{{$data['reward']['id']}}",
                                coupon_code: "{{$data['reward']['coupon_code']}}",
                                merchant_id: "{{$data['merchant_id']}}",
                                email_variant: "{{$data['email_variant'] ?? ''}}"
                            }
                        )
                    );
                }

                global.initAnalytics = initAnalytics;
            })(window.RZP = window.RZP || {});
        </script>

        <script type="text/javascript" src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="window.RZP.initAnalytics()" async></script>

        <style>
            body {
                margin: 0px;
                font-family: 'Helvetica', 'Arial', sans-serif;
            }
            .header {
                height: 100px;
                text-align: center;
                background-color: rgb(95,144,233);
            }
            .header div {
                padding-top: 27px;
                color: white;
                font-size: 20px;
                font-weight: 500;
            }
            .card {
                width: calc(46000% - 211600px);
                max-width: 460px;
                min-width: 308px;
                margin-left: auto;
                margin-right: auto;
                box-sizing: border-box;
                padding-left: 20px;
                padding-right: 20px;
                border-radius: 2px;
                background-color: #FFFFFF;
                padding-bottom: 16px;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
                position: relative;
                top: -35px;
            }
            .card .display {
                text-align: center;
                font-size: 16px;
                color: #525A76;
                line-height: 17px;
                padding-top: 16px;
            }
        </style>
    </head>
<body>
    @if (substr($data['reward']['terms'], 0, 4) == "http")
        <script>
            window.location.href = "{{$data['reward']['terms']}}";
        </script>
    @endif
    <div style="background-color: #f1f1f1; min-height: 100vh;">
        <div class="header">
            <div>Razorpay</div>
        </div>

        <div class="card">
            <div class="display">
                {{$data['reward']['display_text']}}
            </div>

            <div style="text-align: center; margin-top: 12px;">
                <img src="{{$data['reward']['logo']}}" style="height: 47px;" />
                <div style="color: #525A76;">
                    @if (isset($data['reward']['merchant_website_redirect_link']) and $data['reward']['merchant_website_redirect_link'] != '' )
                        <a onclick="handleOfferNameClick()" href="{{$data['reward']['merchant_website_redirect_link']}}" target="_blank" style="color: #2F58E4; text-decoration: none;">
                            {{$data['reward']['name']}}
                            <img src="https://cdn.razorpay.com/static/assets/email/ic-navigate.png" style="margin-left: 5px; height: 11px;" />
                        </a>
                    @else
                        {{$data['reward']['name']}}
                    @endif
                </div>
            </div>

            <div style="margin-top: 25px; border: 1px dashed #DCDCDC; background: #F8F8F8; text-align: center;">
                <div style="font-size: 12px; line-height: 17px; color: #525A76; margin-top: 14px;">
                    Use code:
                </div>
                <div id="coupon-code" style="font-size: 25px; font-weight: 900; text-transform: uppercase; color: #2F58E4; line-height: 138%;">
                    {{$data['reward']['coupon_code']}}
                </div>
                <div style="font-size: 12px; line-height: 17px; color: #000000; margin-bottom: 15px;">
                    <span onclick="copyDivToClipboard()" style="cursor: pointer;">
                        <img src="https://cdn.razorpay.com/static/assets/copy-icon.svg" style="vertical-align: sub;" />
                        <span style="opacity: 0.4;">Copy Code</span>
                    </span>
                </div>
            </div>

            <div style="margin-top: 25px;">
                <div style="color: #525A76; font-size: 13px; font-weight: 600; line-height: 17px;">
                    Terms & Conditions to use Code:
                </div>
                <ul style="padding-inline-start: 15px; color: #525A76; font-size: 12px; font-weight: 400;">
                    <li style="line-height: 17px;">
                        Copy the coupon code to use while paying for a product from the {{$data['reward']['name']}}.
                    </li>
                    @if (isset($data['reward']['flat_cashback']) and isset($data['reward']['min_amount']))
                        <li style="line-height: 17px;">
                            By using the code {{$data['reward']['coupon_code']}}, you can avail discount of
                            <span id="flat_amount_span"></span> on minimum purchase of
                            <span id="min_amount_span"></span>. Discount will be shared as cashback
                            to the account used while paying.
                            @if (isset($data['reward']['max_cashback']))
                                &nbsp;Maximum applicable discount is <span id="cashback_amount_span"></span>.
                            @endif
                        </li>
                    @elseif (isset($data['reward']['percent_rate']) and isset($data['reward']['min_amount']))
                        <li style="line-height: 17px;">
                            By using the code {{$data['reward']['coupon_code']}}, you can avail discount of
                            <span id="percent_rate_span"></span> on minimum purchase of <span id="min_amount_span"></span>. Discount will be shared as cashback to the account used while paying.
                            @if (isset($data['reward']['max_cashback']))
                                &nbsp;Maximum applicable discount is <span id="cashback_amount_span"></span>.
                            @endif
                        </li>
                    @endif
                    <li style="line-height: 17px;">
                        This code expires on <span id="ends_at_span"></span>.
                    </li>
                    @foreach (explode('.', $data['reward']['terms']) as $item)
                        <li style="line-height: 17px;">
                            {{$item}}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div style="border: 1px solid rgb(95,144,233); margin-top: 45px;"></div>

            <div style="text-align: center; margin-top: 50px;">
                <span style="color: #000000; opacity: 0.4; font-size: 14px;">Powered by </span>
                <a href="https://razorpay.com/" target="_blank">
                    <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" style="position: relative; top: 3px; left: 2px; height: 17px;" />
                </a>
            </div>
        </div>
    </div>
    <script>
        var ends_at = new Intl.DateTimeFormat('en-GB', { year: 'numeric', month: 'long', day: 'numeric' }).format("{{$data['reward']['ends_at']}}" * 1000);
        document.getElementById('ends_at_span').innerHTML = ends_at;
        if("{{$data['reward']['flat_cashback']}}" != "") {
            var flat_amt = "{{$data['reward']['flat_cashback']}}" / 100;
            document.getElementById('flat_amount_span').innerHTML = flat_amt+' INR';
        }
        if("{{$data['reward']['percent_rate']}}" != "") {
            var percent = "{{$data['reward']['percent_rate']}}" / 100;
            document.getElementById('percent_rate_span').innerHTML = percent+' %';
        }
        if("{{$data['reward']['min_amount']}}" != "") {
            var min_amt = "{{$data['reward']['min_amount']}}" / 100;
            document.getElementById('min_amount_span').innerHTML = min_amt+' INR';
        }
        if("{{$data['reward']['max_cashback']}}" != "") {
            var cash_back_amt = "{{$data['reward']['max_cashback']}}" / 100;
            document.getElementById('cashback_amount_span').innerHTML = cash_back_amt+' INR';
        }
        function handleOfferNameClick(e){
            triggerAnalytics('offer_name_clicked');
        }

        function triggerAnalytics(event) {
            const urlArr = window.location.href.split("/");
            const paymentId = urlArr[urlArr.length-2];

            if(window.rzpQ && window.rzpQ.push){
            window.rzpQ.push(
                        window.rzpQ.now().merchantActions().success(
                            'reward_terms_page.'+ event,
                            {
                                payment_id: paymentId,
                                reward_id: "{{$data['reward']['id']}}",
                                coupon_code: "{{$data['reward']['coupon_code']}}",
                                merchant_id: "{{$data['merchant_id']}}",
                                email_variant: "{{$data['email_variant'] ?? ''}}"
                            }
                        )
                    );
           }
        }
        function copyDivToClipboard() {
            var range = document.createRange();
            range.selectNode(document.getElementById("coupon-code"));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
            window.getSelection().removeAllRanges();// to deselect
            triggerAnalytics("code_copied");
        }
    </script>
</body>
</html>
