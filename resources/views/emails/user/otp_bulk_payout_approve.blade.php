<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>


<div>
    @if ($input['approved_payout_count'] == 0 && $input['rejected_payout_count'] != 0 )
        {{ $otp['otp'] }} is the OTP for your bulk action to  reject {{ $input['rejected_payout_count'] }} payouts amounting to INR {{ $input['rejected_payout_amount'] }}. OTP is valid till {{ epoch_format($otp['expires_at']) }} IST. Do not share with anyone.
    @elseif ($input['rejected_payout_count'] == 0 && $input['approved_payout_count'] != 0)
        {{ $otp['otp'] }} is the OTP for your bulk action to approve {{$input['approved_payout_count'] }} payouts amounting to INR {{ $input['approved_payout_amount'] }}. OTP is valid till {{ epoch_format($otp['expires_at']) }} IST. Do not share with anyone.
    @else
        {{ $otp['otp'] }} is the OTP for your bulk action to approve {{$input['approved_payout_count'] }} payouts amounting to INR {{ $input['approved_payout_amount'] }} and reject {{ $input['rejected_payout_count'] }} payouts amounting to INR {{ $input['rejected_payout_amount'] }}. OTP is valid till {{ epoch_format($otp['expires_at']) }} IST. Do not share with anyone.
    @endif

</div>

</body>
</html>
