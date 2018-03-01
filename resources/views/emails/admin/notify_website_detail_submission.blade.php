<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h2>Website details submission - {{{$merchant_details['business_name']}}}</h2>

        <div>
            <p>Website details has been submitted by {{{$merchant_details['business_name']}}}</p>

            <p>The merchant id is {{$merchant_details['merchant_id']}}</p>

            <p>The contact name for merchant is {{$merchant_details['contact_name']}}</p>

            <p>The website link for the business is <a href="{{$merchant_details['business_website']}}" title="{{{$merchant_details['business_website']}}}">{{{$merchant_details['business_website']}}}</a></p>

            <p>Please verify the details <a href="{{ URL::to('https://dashboard.razorpay.com/admin/merchants/'.$merchant_details['merchant_id']) }}" target="_blank">here</a> and communicate with the merchant as necessary.</p>
        </div>

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
    </body>
</html>
