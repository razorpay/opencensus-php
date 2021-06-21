<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<div>
    <div>
        Hey {{{$merchant['name']}}},
    </div>

    <br />

    <div>
        Glad to see that you have already started using our payment products and accepted more than INR {{{$threshold}}} in payments.
    </div>

    <div>
        Please note that the current limit on accepting payments on your account is INR 15000.
    </div>

    <div>
        To ensure that your business is not impacted due to the above limit, please visit your Razorpay Dashboard and update your KYC details.
    </div>

    <br />

    <div>
        Best regards,
        <br />
        Team Razorpay
    </div>
</div>

<footer style="text-align:center; margin-top: 10px; font-size: 12px;">For more information <a href="{{ 'https://' . $merchant['org']['hostname'] . '/knowledgebase' }}">click here</a>.
    If you still have queries you can raise a support ticket <a href="{{ 'https://' . $merchant['org']['hostname'] . '/support/#request' }}">here</a>.</footer>
</body>
</html>
