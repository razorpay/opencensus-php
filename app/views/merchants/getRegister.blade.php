@extends('layout')

@section('content')
	<div class="grid grid-pad">
		<div class="centered form-wrapper">
			<div class="content">
				<h2>Register</h2>
				<form method="POST" action="/register">
					<div class="text">
						<input type="text" name="email" placeholder="Email" autofocus>
						<input type="password" placeholder="Password" name="password">
						<input type="password" placeholder="Confirm Password" name="password-confirm">
					</div>
					<button id="form-button">Register</button>
				</form>
				<div class="form-footer">
					<div class="footer-text">
						Already have a Razorpay account? <a href="./login">Sign In</a>.
					</div>
				</div>
			</div>
		</div>
	</div>
@stop