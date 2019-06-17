<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{{$merchant['name']}}},</p>

<h2>Start accepting payments with {{{$merchant['org']['business_name']}}} </h2>


<div>
    <p>Start accepting payments</p>

    <p>You can transact in the live mode now. But your settlements will be on hold until you fill the KYC Form.
        In the meanwhile, you can use of the following options to accept payments from customers:</p>


    <dl>
        <dt style="padding: 0.5%;"><b>Payments on your website</b></dt>

        <dd>Integrate Razorpay onto your website. You can use the API keys from your dashboard. Want to know how to integrate?</dd>
        <dd><a href="https://razorpay.com/docs/" target="_blank">Guide to go live</a></dd>
    </dl>


    <dl>
        <dt style="padding: 0.5%"><b>Payments without integration</b></dt>

        <dd>In case you do not have a website or an app, you can start receiving payments right away using {{{$merchant['org']['business_name']}}} products</dd>
        <dd><a href="https://{{{$merchant['org']['hostname']}}}/#/app/dashboard?products" target="_blank">View products</a></dd>

    </dl>

    <dl>
        <dt style="padding: 0.5%"><b>Payment Methods</b></dt>

        <dd>You can now start accepting payments using these methods - Debit and Credit Cards, Netbanking, UPI and Wallets</dd>
    </dl>
</div>

<div>
    <p>
        Regards,<br>
        Team {{{$merchant['org']['business_name']}}}
    </p>

</div>
</body>
</html>
