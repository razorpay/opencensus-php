<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Password Reset</h2>

		<div>
			To reset your password, <a href="{{ URL::to('/#/access/resetpwd/'.$token) }}" target="_blank">click here</a>. <br/>

			Or you may open the following link in your browser: <br/>
			<a href="{{ URL::to('/#/access/resetpwd/'.$token) }}" target="_blank">{{ URL::to('/#/access/resetpwd/'.$token) }}</a>
		</div>

		<div>
			<p>
			--<br/>
			The Razorpay Team <br/>
			<a href="mailto:contact@razorpay.com">contact@razorpay.com</a>
			</p>
			<a href="https://razorpay.com" target="_blank">
				<img style="width:200px; height:auto;" src="<?php echo $message->embed(public_path().'/img/logo_black.png'); ?>">
			</a>
		</div>
	</body>
</html>
