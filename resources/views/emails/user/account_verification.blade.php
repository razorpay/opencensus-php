<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<h2>Welcome to {{ $org['business_name'] }}</h2>

<div>
    <p>To activate your account, please click <a href = "{{ URL::to('/#/access/confirm/'. $token) }}" >here</a>.</p>

    <p>Alternatively, open the following link in your browser:<br/>
        <a href = "{{ 'https://' .$org['hostname'] .'/#/access/confirm/'. $token }}" >{{ 'https://' .$org['hostname'] .'/#/access/confirm/'. $token }}</a>
</div>

<div>
    <p>
        --<br/>
        The {{ $org['display_name'] }} Team <br/>
        <a href="mailto: {{ $org['signature_email'] }}">{{ $org['signature_email'] }}</a>
    </p>

    @if ($org['custom_code'] === 'rzp')
        <a href="https://razorpay.com" target="_blank">
            <img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
        </a>
    @elseif ($org['login_logo_url'] !== '')
        <a href="{{ 'https://' .$org['hostname'] }}" target="_blank">
            <img style="width:200px; height:auto;" src="{{ $org['login_logo_url'] }}">
        </a>
    @endif
</div>
</body>
</html>
