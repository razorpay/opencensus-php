<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>


        <h2>Your {{ $business_name }} Account is pending approval</h2>

        <div>
            <p>Hi {{{$merchant_details['contact_name']}}},</p>

            <p>Your {{ $business_name }} activation form is complete and we have notified the admins to verify the details. We will communicate to you if anything else is required.</p>

            <p>Meanwhile, you can integrate with {{ $business_name }} in test mode and feel free to communicate with us at support@razorpay.com in case of any issues or queries.</p>
        </div>

        <div>
            <p>
            --<br/>
            The {{ $display_name }} Team <br/>
            <a href="mailto: {{ $signature_email }}">{{ $signature_email }}</a>
            </p>
            <a href="https://razorpay.com" target="_blank">
                <img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
            </a>
        </div>
    </body>
</html>
