@extends('layout')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Reset Your Password</h2>
                {{ Form::open(array('action' => array('PasswordController@postRemind'))) }}
                    <div class="text">
                        <input type="text" name="email" placeholder="Email" autofocus>
                    </div>
                    <button id="form-button">Send Reset Password Email</button>
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
                @elseif
                <ul class="success-message">
                    <li>
                        {{ Session::get('status') }}
                    </li>
                </ul>
                @endif
            </div>
        </div>
    </div>
@stop