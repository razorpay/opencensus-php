@extends('layout')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Hi, {{{ $data['name'] }}}!</h2>
                <p class="message">
                    Please save your Razorpay API credentials carefully. Click <a href="/keys/csv?id={{{ $data['key']['id'] }}}&secret={{{ $data['key']['secret'] }}}">here</a> to download.
                </p>
                <div class="credentials boxed">
                    <div class="key">ID</div>
                    <div class="value">{{{ $data['key']['id'] }}}</div><br>
                    <div class="key">Secret</div>
                    <div class="value">{{{ $data['key']['secret'] }}}</div>
                </div>
                <div class="form-footer">
                    <div class="footer-text">
                        Saved the credentials? <a href="/login">Continue to dashboard</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop