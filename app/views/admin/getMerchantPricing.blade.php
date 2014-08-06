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
            <h2>Manage Merchant Pricing</h2>
            <h4>Merchant Id: {{{$details['merchant']['id']}}}</h4>
            <h4>Merchant Name: {{{$details['merchant']['name']}}}</h4>
            <h4>Email: {{{$details['merchant']['email']}}}</h4>
            <br/><br/>
            <h3>Pricing: </h3>
            @if(empty($pricing) === false)
                <h4>Pricing Plan Id: {{{$pricing['id']}}}</h4>
                <h4>Pricing Plan Name: {{{$pricing['name']}}}</h4>
                <h4><a href="/admin/pricing/{{{$pricing['id']}}}">See Plan Rules</a></h4>
            @else
                No Plan Assigned
            @endif
            <br/><br/>
            <h4>Assign New Pricing Plan (Replaces old one)</h3>
            <form method="POST">
            <label for="pricing_plan_id">Pricing Plan:</label>
            <select class="form-control" name="pricing_plan_id"/>
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
            <li>To create/view pricing plans, click on pricing in sidebar</li>
            </p>
        </div>
    </div>
@stop