@extends('admin.layoutGenerated')

@section('panelcontent')
@if(Session::has('status'))
    @foreach(Session::get('status') as $message)
        <div class="alert alert-info alert-dismissable">
        {{{$message}}}
        </div>
    @endforeach
@endif
    <div class="centered form-wrapper">
        <div class="content">
            <h2>Manage Merchant Terminal</h2>
            <h4>Merchant Id: {{{$details['merchant']['id']}}}</h4>
            <h4>Merchant Name: {{{$details['merchant']['name']}}}</h4>
            <h4>Email: {{{$details['merchant']['email']}}}</h4>

            <br/>
            <h3>Terminal: </h3>
            @if(empty($terminal) === false)
                <h4>Gateway: {{{$terminal['gateway']}}}</h4>
                <h4>Merchant Id: {{{$terminal['gateway_merchant_id']}}}</h4>
                <h4>Terminal Id: {{{$terminal['gateway_terminal_id']}}}</h4>
            @else
                <h4>Assign New Terminal Id</h4>
                <form method="POST">
                <label for="gateway">Gateway:</label>
                <select class="form-control" name="gateway">
                    <option value="HDFC" selected>HDFC</option>
                </select>
                <label for="gateway_merchant_id">MID:</label>
                <input type="text" class="form-control" name="gateway_merchant_id" placeholder="MID"/>
                <label for="gateway_terminal_id">TID:</label>
                <input type="text" class="form-control" name="gateway_terminal_id" placeholder="TID"/>
                <label for="gateway_terminal_password">TID Password:</label>
                <input type="text" class="form-control" name="gateway_terminal_password" placeholder="TID Password"/>
                <label for="gateway_terminal_password_confirmation">Confirm TID Password:</label>
                <input type="text" class="form-control" name="gateway_terminal_password_confirmation" placeholder="TID Password Confirm"/>
                <button class="btn btn-primary" type="submit">Submit</button>
                <input type="hidden" name="_token" value="{{{csrf_token()}}}">
                </form>
            @endif
        </div>
    </div>
@stop