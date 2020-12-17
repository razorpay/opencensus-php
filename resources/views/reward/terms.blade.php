<!DOCTYPE html>
<html lang="en">
    <head>
        <title> Reward Terms - {{$data['name']}} </title>

        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

        <script>
            'use strict';
            (function (global) {
        
                function initAnalytics() {
                    analytics.init(['ga', 'hotjar'], window.location.hostname.indexOf('razorpay.com') < 0);
                    analytics.track('ga', 'pageview', {
                        eventCategory: 'Checkout Rewards',
                        eventAction: `Visited Terms page - ${$data['id']}`
                    });
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
    @if (substr($data['terms'], 0, 4) == "http")
        <script>
            window.location.href = "{{$data['terms']}}";
        </script>
    @endif
    <div style="background-color: #f1f1f1; min-height: 100vh;">
        <div class="header">
            <div>Razorpay</div>
        </div>
        
        <div class="card">
            <div class="display">
                {{$data['display_text']}}
            </div>
            
            <div style="text-align: center; margin-top: 12px;">
                <img src="{{$data['logo']}}" style="height: 47px;" />
                <div style="color: #525A76;">
                    @if (isset($data['merchant_website_redirect_link']) and $data['merchant_website_redirect_link'] != '' )
                        <a href="{{$data['merchant_website_redirect_link']}}" target="_blank" style="color: #2F58E4; text-decoration: none;">
                            {{$data['name']}}
                            <img src="https://cdn.razorpay.com/static/assets/email/ic-navigate.png" style="margin-left: 5px; height: 11px;" />
                        </a>
                    @else
                        {{$data['name']}}
                    @endif
                </div>
            </div>
            
            <div style="margin-top: 25px; border: 1px dashed #DCDCDC; background: #F8F8F8; text-align: center;">
                <div style="font-size: 12px; line-height: 17px; color: #525A76; margin-top: 14px;">
                    Use code:
                </div>
                <div id="coupon-code" style="font-size: 25px; font-weight: 900; text-transform: uppercase; color: #2F58E4; line-height: 138%;">
                    {{$data['coupon_code']}}
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
                        Copy the coupon code to use while paying for a product from the {{$data['name']}}.
                    </li>
                    @if (isset($data['flat_cashback']) and isset($data['min_amount']))
                        <li style="line-height: 17px;">
                            By using the code {{$data['coupon_code']}}, you can avail discount of 
                            <span id="flat_amount_span"></span> on minimum purchase of 
                            <span id="min_amount_span"></span>. Discount will be shared as cashback 
                            to the account used while paying.
                            @if (isset($data['max_cashback']))
                                &nbsp;Maximum applicable discount is <span id="cashback_amount_span"></span>.
                            @endif
                        </li>
                    @elseif (isset($data['percent_rate']) and isset($data['min_amount']))
                        <li style="line-height: 17px;">
                            By using the code {{$data['coupon_code']}}, you can avail discount of
                            <span id="percent_rate_span"></span> on minimum purchase of <span id="min_amount_span"></span>. Discount will be shared as cashback to the account used while paying.
                            @if (isset($data['max_cashback']))
                                &nbsp;Maximum applicable discount is <span id="cashback_amount_span"></span>.
                            @endif
                        </li>
                    @endif
                    <li style="line-height: 17px;">
                        This code expires on <span id="ends_at_span"></span>.
                    </li>
                    @foreach (explode('.', $data['terms']) as $item)
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
        var ends_at = new Intl.DateTimeFormat('en-GB', { year: 'numeric', month: 'long', day: 'numeric' }).format("{{$data['ends_at']}}" * 1000);
        document.getElementById('ends_at_span').innerHTML = ends_at;
        if("{{$data['flat_cashback']}}" != "") {
            var flat_amt = "{{$data['flat_cashback']}}" / 100;
            document.getElementById('flat_amount_span').innerHTML = flat_amt+' INR';
        }
        if("{{$data['percent_rate']}}" != "") {
            var percent = "{{$data['percent_rate']}}" / 100;
            document.getElementById('percent_rate_span').innerHTML = percent+' %';
        }
        if("{{$data['min_amount']}}" != "") {
            var min_amt = "{{$data['min_amount']}}" / 100;
            document.getElementById('min_amount_span').innerHTML = min_amt+' INR';
        }
        if("{{$data['max_cashback']}}" != "") {
            var cash_back_amt = "{{$data['max_cashback']}}" / 100;
            document.getElementById('cashback_amount_span').innerHTML = cash_back_amt+' INR';
        }
        function copyDivToClipboard() {
            var range = document.createRange();
            range.selectNode(document.getElementById("coupon-code"));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
            window.getSelection().removeAllRanges();// to deselect
        }
    </script>
</body>
</html>