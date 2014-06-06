@extends('layout')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Register</h2>
                <form method="POST" action="/register">
                    <div class="text">
                        <input type="text" name="name" placeholder="Name" @if (isset($data['name'])) value="{{{$data['name']}}}" @else autofocus @endif>
                        <input type="text" name="email" placeholder="Email" @if (isset($data['email'])) value="{{{$data['email']}}}" @else autofocus @endif>
                        <input type="password" placeholder="Password" name="password">
                        <input type="password" placeholder="Confirm Password" name="password_confirmation">
                    </div>
                    <button id="form-button">Register</button>
                </form>
                <div class="form-footer">
                    <div class="footer-text">
                        Already have a Razorpay account? <a href="./login">Sign In</a>.
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