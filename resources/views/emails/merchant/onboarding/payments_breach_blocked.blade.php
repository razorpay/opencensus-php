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
        You have already hit the maximum payment limit of INR 15000 on your account and your payments are paused until you fill your KYC.
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
