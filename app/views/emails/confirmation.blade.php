<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Welcome to Razorpay</h2>

		<div>
			<p>To activate your account, please click <a href = "{{ URL::to('/#/access/confirm/'.$merchant['confirm_token']) }}" >here</a>.</p>

			<p>Alternatively, open the following link in your browser:<br/>
			<a href = "{{ URL::to('/#/access/confirm/'.$merchant['confirm_token']) }}" >{{ URL::to('/#/access/confirm/'.$merchant['confirm_token']) }}</a>
		</div>

		<div>
			<p>--<br/>
			The Razorpay Team <br/>
			contact@razorpay.com </br/>
			<img src="<?php echo $message->embed('public/img/logo_black.png'); ?>">
			
	</body>
</html>