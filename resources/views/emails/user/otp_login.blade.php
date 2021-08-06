<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
    {{ $otp['otp'] }} is the OTP to login using email. OTP is valid till {{ epoch_format($otp['expires_at']) }} IST. Do not share it with anyone.
</div>

</body>
</html>
