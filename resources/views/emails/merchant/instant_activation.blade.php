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

    <p>You can now <b>start accepting online payments using Razorpay</b> using a variety of payment methods.</p>


    <dl>
        <dt style="padding: 0.5%;"><b>Accept Payments on your website</b></dt>

        <dd>Refer to our <a href="https://razorpay.com/docs/payment-gateway/integrations-guide/" target="_blank">Integration Guide</a> to integrate Razorpay onto your website.</dd>
    </dl>


    <dl>
        <dt style="padding: 0.5%"><b>Accept Payments without integration</b></dt>

        <dd>In case you do not have a website or an app and do not want to integrate, you can use one of our other products to start receiving payments right away.
            Refer to our <a href="https://razorpay.com/docs/" target="_blank">Product Document</a> for more information.</dd>

    </dl>

    <dd>Please note:<i> You will only be able to receive settlements for payments collected using the
            Razorpay payment gateway to your linked bank account after your KYC is successfully verified.</i>
    </dd>


    <dl>
        <dt style="padding: 0.5%;"><b>Recommended Reading for you to get started and get to know us better</b></dt>
        <dd>
            <ul>
                <li>
                    <a href="https://razorpay.com/docs/payment-gateway/getting-started-guide/">Payment Flow</a>
                </li>
                <li>
                    <a href="https://razorpay.com/docs/payment-gateway/orders/">Auto Capture</a>
                </li>
                <li>
                    <a href="https://razorpay.com/docs/payment-gateway/dashboard-guide/">Dashboard Guide</a>
                </li>
            </ul>
        </dd>
    </dl>


    <dd>For any further queries or clarifications, feel free to reach out to our </dd>
    <dd><a href="https://razorpay.com/support" target="_blank">Support Team</a></dd>

</div>

<div>
    <p>
        Regards,<br>
        Team {{{$merchant['org']['business_name']}}}
    </p>
</div>
</body>
</html>