<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
  {{ $otp['otp'] }} is the OTP to {{ $formatted_action }}. OTP is usable once & is valid till {{ epoch_format($otp['expires_at']) }} IST. Please do not share it with anyone.
</div>

</body>
</html>
