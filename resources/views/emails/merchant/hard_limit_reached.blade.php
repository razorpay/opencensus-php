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
        Your account is now under review, and our compliance team is reviewing your submitted KYC documents again.
    </div>

    <div>
        What this means for you:
        <br/>
        1. You can continue accepting payments while the review is in progress.
        <br/>
        2. Your past payments from customers are safely kept on hold and will be settled immediately once the review is done successfully.
    </div>

    <div>
        We will reach out to you here for any clarifications, and we assure you that the review will be done in less than 48 hours.
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
