<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h2>New Activation Form submission - {{{$customer['id']}}}</h2>

        <div>
            <p>Activation form has been submitted by merchant id: {{{$customer['id']}}}</p>

            <p>Please verify the details and communicate with the merchant as necessary.</p>
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
<?php die(); ?>