Hi!

<br><br>

{{ $loggedInUser['name'] }} has invited you to join their team ({{$invitation_array['merchant']['name']}}).

If you do not already have an account, you may click the following link to get started:

<br><br>

<a href="{{ url('/#/access/signup?invitation='.$invitation_array['token']) }}">{{ URL::to('/#/access/signup?invitation='.$invitation_array['token']) }}</a>

<br><br>

See you soon!

<br>

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
