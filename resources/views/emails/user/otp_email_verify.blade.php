
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<div>
    <p>To finish setting up your Razorpay account, we just need to make sure this email address is yours.</p>
    <p><strong style='font-size:18px'>{{ $otp['otp'] }}</strong> is the verification OTP to {{ $formatted_action }}.</p>
    <p>OTP is usable once & is valid till {{ epoch_format($otp['expires_at']) }} IST. Please do not share it with anyone.</p>
    <p>If you didn't request this code, you can safely ignore this email. Someone else might have typed your email address by mistake.</p>
    Thanks,
    <br>
    Team Razorpay
</div>
</body>
</html>
