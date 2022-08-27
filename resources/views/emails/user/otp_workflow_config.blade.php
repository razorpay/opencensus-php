<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
    {{ $otp['otp'] }} is the OTP to {{ $input['workflow_action'] }} your workflow config in RazorpayX. OTP is valid till {{ epoch_format($otp['expires_at']) }} IST. Please do not share with anyone.
</div>

</body>
</html>
