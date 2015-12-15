<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>


        <h2>Your Razorpay Account is pending approval</h2>

        <div>
            <p>Hi {{{$merchant_details['contact_name']}}},</p>

            <p>Your Razorpay activation form is complete and we have notified the admins to verify the details. We will communicate to you if anything else is required.</p>

            <p>Meanwhile, you can integrate with Razorpay in test mode and feel free to communicate with us at support@razorpay.com in case of any issues or queries.</p>
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
