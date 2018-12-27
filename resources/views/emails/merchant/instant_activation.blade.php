<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<h2>Start accepting payments with {{{$merchant['org']['business_name']}}} </h2>

<div>
    <p>Congratulations! Your Razorpay account has been activated.</p>

    <p>You can now <b>start accepting online payments using Razorpay</b> using a variety of payment methods in any of
        the following ways</p>


    <dl>
        <dt style="padding: 0.5%;"><b>Accept Payments on your website</b></dt>

        <dd>Integrate Razorpay onto your website. You can use the API keys from your dashboard. Want to know how to integrate?</dd>
        <dd><a href="https://docs.razorpay.com/" target="_blank">Guide to go live</a></dd>
    </dl>


    <dl>
        <dt style="padding: 0.5%"><b>Accept Payments without integration</b></dt>

        <dd>In case you do not have a website or an app, you can start receiving payments right away using {{{$merchant['org']['business_name']}}} products</dd>
        <dd><a href="https://{{{$merchant['org']['hostname']}}}/#/app/dashboard?products" target="_blank">View products</a></dd>

    </dl>

    <dl>
        <dt>Alternately, you can start accepting payments in <a href = "{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)}}" >your Razorpay X account </a> by two methods -</dt>

        <dd>a) NEFT/RTGS transfers from other banks - Click to know your account details for bank transfers</dd><br>
        <dd> OR </dd><br>
        <dd>b) Routing your Razorpay payment gateway settlements directly to your Razorpay X account - <a href ="https://razorpay.com/docs/razorpayx/" target="_blank" >Click to learn about this feature</a></dd>
    </dl>

    <dd><b>Please note:</b><i> You will only be able to receive settlements for payments collected using the
            Razorpay payment gateway to your linked bank account or create payouts via Razorpay X after
            completing the KYC verification successfully.</i> </dd>

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
