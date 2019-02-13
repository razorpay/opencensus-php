<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<h2>Settlements enabled for your Razorpay account</h2>

<div>
    <p>Hurray! Your KYC documents have been successfully verified for your Razorpay account.</p>
    <dl>
    <dd>You will start to <b>receive settlements</b> for the payments collected online using Razorpay products to
        your linked bank account going forward.
    </dd><br>
    </dl>

    <dd>For any further queries or clarifications, feel free to reach out to us by visiting - </dd>
    <dd><a href="https://razorpay.com/support" target="_blank">https://razorpay.com/support</a></dd>

</div>

<div>
    <p>
        Regards,<br>
        Team {{{$merchant['org']['business_name']}}}
    </p>

</div>
</body>
</html>
