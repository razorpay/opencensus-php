Hi!

<br><br>

{{ $invitation->merchant->owners()->first()->name }} has invited you to join their team with roles as {{ $invitation->role }}!

<br><br>

Since you already have an account, you may accept the invitation from your
account settings screen.

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
