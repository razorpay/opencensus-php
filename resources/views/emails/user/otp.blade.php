<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
  {{ $otp['otp'] }} is your Razorpay OTP to {{ $action }}. This code is valid till {{ epoch_format($otp['expires_at']) }} IST. Do not share this with anyone.
</div>

</body>
</html>
