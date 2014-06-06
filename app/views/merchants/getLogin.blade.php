@extends('layout')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Sign In</h2>
                <form method="POST" action="/login">
                    <div class="text">
                        <input type="text" name="email" placeholder="Email" autofocus>
                        <input type="password" placeholder="Password" name="password">
                    </div>
                    <div class="row">
                        <div class="remember col-1-2">
                            <input type="checkbox" name="remember" id="remember-input"><label for="remember-input">Remember Me</label>
                        </div>
                        <div class="forgot-wrap col-1-2">
                            <a class="forgot" href="./reset">Forgot Password?</a>
                        </div>
                    </div>
                    <button id="form-button">Sign In</button>
                </form>
                <div class="form-footer">
                    <div class="footer-text">
                        Don't have a Razorpay account? <a href="./register">Sign Up</a> today.
                    </div>
                </div>
                @if (isset($error) && !empty($error))
                    <ul class="error-message">
                    @foreach($error as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@stop