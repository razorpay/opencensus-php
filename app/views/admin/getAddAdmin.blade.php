@extends('admin.layoutGenerated')

@section('panelcontent')
    <div class="centered form-wrapper">
        <div class="content">
            <h2>Add new Admin</h2>
            {{ Form::open(array('action' => array('AdminController@postAddAdmin'))) }}
                @if (Session::has('create') and Session::get('create') === TRUE)
                <div class="alert alert-dismissable alert-success">Admin Added! </div>
                @endif
                <div class="text">
                    <input type="text" name="name" placeholder="Name" autofocus required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
                    <select class="form-control m-bot15" name="superadmin" required>
                        <option value="0" selected >Standard Admin</option>
                        <option value="1">Super Admin</option>
                    </select>
                </div>
                    <button id="form-button">Submit</button>
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