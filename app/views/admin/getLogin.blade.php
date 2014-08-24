@extends('admin.layoutGenerated')

@section('content')
    <div class="grid grid-pad">
        <div class="centered form-wrapper">
            <div class="content">
                <h2>Admin Log In</h2>
                {{ Form::open(array('action' => array('AdminController@postLogin'))) }}
                    <div class="text">
                        <input type="text" name="username" placeholder="Username" autofocus>
                        <input type="password" placeholder="Password" name="password">
                    </div>
                    <button id="form-button">Sign In</button>
                {{ Form::close() }}

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