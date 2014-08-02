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
            <h2>Activate Merchant</h2>
            <h4>Merchant Id: {{{$details['merchant']['id']}}}</h4>
            <h4>Email: {{{$details['merchant']['email']}}}</h4>
            
            <form method="POST">
            <label for="tid">TID:</label>
            <input type="text" class="form-control" name="tid" placeholder="TID"/>
            <label for="tid_password">TID Password:</label>
            <input type="text" class="form-control" name="tid_password" placeholder="TID Password"/>
            <label for="tid_password_confirmation">Confirm TID Password:</label>
            <input type="text" class="form-control" name="tid_password_confirmation" placeholder="TID Password Confirm"/>
            <label for="pricing_plan">Pricing Plan:</label>
            <select class="form-control" name="pricing_plan"/>
                <option>None</option>
                @foreach($pricing_plans['data'] as $pricing_plan)
                <option value="{{{$pricing_plan['id']}}}">{{{$pricing_plan['name']}}}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit">Submit</button>
            <input type="hidden" name="_token" value="{{{csrf_token()}}}">
            </form>
            <p>
            Note:
            <li>To create new pricing plan, click here</li>
            </p>
        </div>
    </div>
@stop