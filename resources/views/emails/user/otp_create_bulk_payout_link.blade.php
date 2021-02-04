<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
    @if(array_key_exists('total_payout_link_amount', $input))
    {{ $otp['otp'] }} is your OTP to create bulk payout links for INR {{ amount_format_IN($input['total_payout_link_amount']) }} against RazorpayX account {{ mask_except_last4($input['account_number']) }}. OTP is usable once & is valid till {{ epoch_format($otp['expires_at']) }} IST. Please do not share it with anyone.
    @else
        {{ $otp['otp'] }} is the OTP for bulk payout links from RazorpayX account {{ mask_except_last4($input['account_number']) }}. OTP is valid till {{ epoch_format($otp['expires_at']) }} IST. Please do not share it with anyone.
    @endif
</div>

</body>
</html>
