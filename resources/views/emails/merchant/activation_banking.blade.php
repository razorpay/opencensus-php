<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<h2>Payouts & Settlements enabled for your Razorpay account </h2>

<div>
    <p>Hurray! Your KYC documents have been successfully verified for your Razorpay account.</p>

    <dl>
    <dd>You can now make payouts to other bank accounts using NEFT, RTGS, IMPS or UPI transfers via Razorpay X.</dd><br>

    <dd><a href = "https://razorpay.com/docs/razorpayx/">Click to learn how to make payouts using Razorpay X</a></dd><br>

        <dd>We also have <b>enabled settlements on your Razorpay payment gateway account.</b> You will start receiving
        settlements for payments collected online using Razorpay products to your linked bank account going forward
        .</dd><br>

    <dd>Alternately, you can route your payment gateway settlements to <a href = "{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)}}" >your Razorpay X account.</a>.</dd><br>

    <dd><a href="https://razorpay.com/docs/razorpayx/" target="_blank">Click to learn about this feature</a></dd>
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
