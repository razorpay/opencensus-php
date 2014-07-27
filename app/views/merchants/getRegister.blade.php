@extends('layoutGenerated')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Register</h2>
                {{ Form::open(array('action' => array('MerchantController@postRegister'))) }}
                    <div class="text">
                        <input type="text" name="name" placeholder="Bussiness/Individual Name" 
                            @if (Session::has('data') and isset(Session::get('data')['name'])) 
                                value="{{{Session::get('data')['name']}}}"
                            @else 
                                autofocus 
                            @endif
                        >
                        <input type="text" name="email" placeholder="Contact Email" 
                            @if (Session::has('data') and isset(Session::get('data')['email']))  
                                value="{{{Session::get('data')['email']}}}" 
                            @else 
                                autofocus 
                            @endif>
                        <input type="password" placeholder="Password" name="password">
                        <input type="password" placeholder="Confirm Password" name="password_confirmation">
                    </div>
                    <button id="form-button">Register</button>
                {{ Form::close() }}
                <div class="form-footer">
                    <div class="footer-text">
                        Already have a Razorpay account? <a href="/login">Sign In</a>.
                    </div>
                </div>
                @if (Session::has('error'))
                    <ul class="error-message">
                    @foreach(Session::get('error') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@stop