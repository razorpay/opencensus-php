<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<p>Dear Customer,</p>

<div>
    <p>
        INR {{ amount_format_IN($amount) }} has been credited to your RazorpayX account {{ mask_except_last4($account_number) }}.
    </p>
    @if (empty($adjustment_description) === false)
    <p>
        Your transaction details are: {{ $adjustment_description }}.
    </p>
    @endif
    <p>
        Please contact <a href="https://x.razorpay.com/?support=ticket" target="_blank">RazorpayX Support</a> to report if this transaction was not authorized by you.
    </p>
</div>

<div>
    <p>
        Regards,
        <br>
        Team RazorpayX
    </p>
</div>

</body>
</html>
