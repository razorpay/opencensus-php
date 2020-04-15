<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<p>Dear Customer,</p>

<div>
    @if ($source['amount'] > 0)
        <p>
            INR {{ amount_format_IN($txn['amount']) }} has been credited to your RazorpayX
            account {{ mask_except_last4($balance['account_number']) }} on {{ $source['created_at_formatted'] }}
            towards {{ $source['description'] }}.
        </p>
    @else
        <p>
            INR {{ amount_format_IN(abs($txn['amount'])) }} has been debited from your RazorpayX
            account {{ mask_except_last4($balance['account_number']) }} on {{ $source['created_at_formatted'] }}
            towards {{ $source['description'] }}.
        </p>
    @endif
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
        <br>
        Team RazorpayX
    </p>
</div>

</body>
</html>
