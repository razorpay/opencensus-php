<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<h2>Settlements & Payouts enabled for your Razorpay account</h2>

<div>
    <p>Hurray! Your KYC documents have been successfully verified for your Razorpay account.</p>
    <dl>
    <dd>You will start to <b>receive settlements</b> for the payments collected online using Razorpay products to
        your linked bank account going forward.</dd><br>

    <dd>We have also enabled <b>payouts</b> on <a href = "{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)}}" >your Razorpay X account </a></dd><br>

    <dd>You can now create bank transfers using your Razorpay X account using NEFT, RTGS, IMPS transfer methods to
        other bank accounts.</dd><br>

    <dd><a href="https://razorpay.com/docs/razorpayx/" target="_blank">Click to learn how to make payouts using Razorpay X</a></dd><br>

    <dd>Alternately, you can route your payment gateway settlements to <a href = "{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)}}" >your Razorpay X account.</a></dd><br>

    <dd><a href="https://docs.razorpay.com/" target="_blank">Click to learn about this feature</a></dd>

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
