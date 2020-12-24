<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h2>New Activation Form submission - {{{$merchant_details['business_name']}}}</h2>

        <div>
            <p>Activation form has been submitted by {{{$merchant_details['business_name']}}}</p>
            <p>The merchant id is {{$merchant_details['merchant_id']}}</p>

            <p>Please verify the details <a href="{{ URL::to('https://dashboard.razorpay.com/admin#/app/merchants/'.$merchant_details['merchant_id'].'/detail') }}" target="_blank">here</a> and communicate with the merchant as necessary.</p>

            <p>The DBA for the merchant is: {{{$merchant_details['business_dba']}}}.</p>

            <p>Contact name for merchant is: {{{$merchant_details['business_name']}}}</p>

            @if (isset($merchant_details['business_website']) === true)
            <p>The website link for the business is: <a href="{{$merchant_details['business_website']}}" title="{{{$merchant_details['business_dba']}}}">{{{$merchant_details['business_dba']}}}</a>.</p>
            @endif

        </div>

        @if ($merchant_details['custom_branding'] === false)
            <div>
                <p>
                --<br/>
                The Razorpay Team <br/>
                <a href="mailto:contact@razorpay.com">contact@razorpay.com</a>
                </p>
                <a href="https://razorpay.com" target="_blank">
                    <img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
                </a>
            </div>
        @elseif ($merchant_details['custom_branding'] === true)
            <div>
                <p>
                --<br/>
                The {{$merchant_details['org_name']}} Team <br/>
                </p>
                <img style="width:200px; height:auto;" src="{{ $merchant_details['email_logo'] }}">
            </div>
        @endif
    </body>
</html>
