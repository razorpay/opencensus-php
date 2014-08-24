@extends('layoutGenerated')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>New {{{$mode}}} Key</h2>
                <p class="message">
                    Please save your Razorpay API credentials carefully. Click <a href="/keys/csv?id={{{ $key['id'] }}}&secret={{{ $key['secret'] }}}">here</a> to download.
                </p>
                <div class="credentials boxed">
                    <div class="key">ID</div>
                    <div class="value">{{{ $key['id'] }}}</div><br>
                    <div class="key">Secret</div>
                    <div class="value">{{{ $key['secret'] }}}</div>
                </div>
                <div class="form-footer">
                    <div class="footer-text">
                        Saved the credentials? <a href="javascript:window.close()">Close this window</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop