@extends('admin.layoutGenerated')

@section('panelcontent')
    <div class="centered form-wrapper">
        <div class="content">
            <h2>Change Admin Password</h2>
            {{ Form::open(array('action' => array('AdminController@postPassword'))) }}
                <div class="text">
                    <input type="password" placeholder="Old Password" name="old_password">
                    <input type="password" placeholder="New Password" name="password">
                    <input type="password" placeholder="Confirm New Password" name="password_confirmation">
                </div>
                <button id="form-button">Update</button>
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
@stop