<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<p>Dear Customer,</p>

<div>
    <p>
        INR {{ amount_format_IN($txn['amount']) }} has been reversed to
        account {{ mask_except_last4($balance['account_number']) }}
        on {{ $source['created_at_formatted'] }} due to {{ $source['failure_reason'] ?? 'reversal' }}.
    </p>
    <p>
        Your transaction reference number is {{ $source['id'] }}.
    </p>
    <p>
        Please contact <a href="https://razorpay.com/support" target="_blank">Razorpay Support</a> to report if this
        transaction was not authorized by you.
    </p>
</div>

<div>
    <p>
        Regards,
        <br/>
        Team RazorpayX
    </p>
</div>

</body>
</html>
