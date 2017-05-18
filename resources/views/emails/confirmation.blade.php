<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Welcome to {{ $business_name }}</h2>

		<div>
			<p>To activate your account, please click <a href = "{{ URL::to('/#/access/confirm/'. $token) }}" >here</a>.</p>

			<p>Alternatively, open the following link in your browser:<br/>
			<a href = "{{ URL::to('/#/access/confirm/'. $token) }}" >{{ URL::to('/#/access/confirm/'. $token) }}</a>
		</div>

		<div>
			<p>
			--<br/>
			The {{ $display_name }} Team <br/>
			<a href="mailto: {{ $signature_email }}">{{ $signature_email }}</a>
			</p>
			<a href="https://razorpay.com" target="_blank">
				<img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
			</a>
		</div>
	</body>
</html>
