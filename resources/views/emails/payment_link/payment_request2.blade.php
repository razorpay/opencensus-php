<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h2>Payment link notification - {{{$payment_link['id']}}}</h2>

        <div>
            <p>Payment link notification - {{{$payment_link['id']}}}</p>
        </div>

        <div>
            <p>
            --<br/>
            The Razorpay Team <br/>
            <a href="mailto:contact@razorpay.com">contact@razorpay.com</a>
            </p>
            <a href="https://razorpay.com" target="_blank">
                <img style="width:200px; height:auto;"
                    src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
            </a>
        </div>
    </body>
</html>
