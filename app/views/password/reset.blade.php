@extends('layout')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Reset Your Password</h2>
                {{ Form::open(array('action' => array('PasswordController@postReset', $token))) }}
                    <div class="text">
                        <input type="text" name="email" placeholder="Email" autofocus>
                        <input type="password" name="password" placeholder="Password">
                        <input type="password" name="password_confirmation" placeholder="Confirm Password">
                        {{ Form::hidden('token', $token) }}
                    </div>
                    <button id="form-button">Set New Password</button>
                {{ Form::close() }}
                <div class="form-footer">
                    <div class="footer-text">
                        Know your password? <a href="/login">Sign In</a>.
                    </div>
                </div>
                @if (Session::has('error'))
                <ul class="error-message">
                    <li>
                        {{ Session::get('error') }}
                    </li>
                </ul>
                @endif
            </div>
        </div>
    </div>
@stop