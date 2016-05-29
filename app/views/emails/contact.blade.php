<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Contact Form Submission - Razorpay.com</h2>

		<div>
			Name: {{{$name}}} <br/>
			Email: {{{$email}}} <br/>
@if(!empty($phone))
            Phone: {{{$phone}}} <br/>
@endif
			Message: <br/>
			{{{$content}}}
		</div>

	</body>
</html>
