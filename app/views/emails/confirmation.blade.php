<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Welcome to Razorpay</h2>

		<div>
			<p>To activate your account, please click <a href = "{{ URL::to('register/confirm', array($merchant['confirm_token'])) }}" >here</a>.</p>

			<p>Alternatively, open the following link in your browser:<br/>
			<a href = "{{ URL::to('confirm', array($merchant['confirm_token'])) }}" >{{ URL::to('confirm', array($merchant['confirm_token'])) }}</a>
		</div>
	</body>
</html>