<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <title>Razorpay OTP Email</title>
</head>
<body>

<div>
    {{ $otp['otp'] }} is your OTP to create payout for INR {{ amount_format_IN($input['amount']) }} against RazorpayX account {{ mask_except_last4($input['account_number']) }}. OTP is usable once & is valid till {{ epoch_format($otp['expires_at']) }} IST. Please do not share it with anyone.
</div>

</body>
</html>
