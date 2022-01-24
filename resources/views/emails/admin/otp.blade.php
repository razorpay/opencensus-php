<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
  {{ $otp['otp'] }} is the OTP to {{ $formatted_action }}. OTP is usable once & is valid till {{ $otp['expires_at'] }} IST. Please do not share it with anyone.
</div>
<div>
    <p>
        <br/>
        {{$org['display_name']}} <br/>
    </p>
    <div>
        <img style="width:200px; height:auto;" src="{{$org['login_logo_url']}}">
    </div>
</div>

</body>
</html>
