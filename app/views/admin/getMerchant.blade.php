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
            <h2>Merchant Activation Details</h2>

            <h4>Merchant Id: {{{$details['merchant']['id']}}}</h4>

            <h4>Email: {{{$details['merchant']['email']}}}</h4>

            <h4>Steps Finished by Merchant</h4>
            @foreach($details['steps_finished'] as $step)
                <input type="checkbox" name="steps_finished[]" value="{{$step}}" checked> Step {{$step+1}}
            @endforeach
            <br/><br/>

            <h4>Pricing Plan:
            @if(empty($pricing_plan))
                No Plan Assigned
            @else
                {{{$pricing_plan['name']}}}
            @endif
            <br/>
            <a href="{{{$details['merchant']['id']}}}/pricing">Modify Pricing Plan</a>
            </h4>
            <br/>

            <h4>Gateway Terminal:
            @if(empty($terminal))
                No terminal Assigned <a href="{{{$details['merchant']['id']}}}/terminal">Add Terminal</a>
            @else
                <br/>
                Gateway: {{{$terminal['gateway']}}} - MID: {{{$terminal['gateway_merchant_id']}}} - TID: {{{$terminal['gateway_terminal_id']}}}
            @endif
            </h4>
            <br/>

            <h4>Activation Status:
            @if(in_array(5, $details['steps_finished']) and (int)$details['merchant']['live'] === 0)
                Inactive <br/>
                    <a
                    href="{{{$details['merchant']['id']}}}/activate?_token={{{csrf_token()}}}"
                    onclick="if (confirm('Are you sure you want to activate this merchant? (Ensure you have checked all his details)')){return true;} return false;">
                    Activate Merchant
                    </a>
            @elseif(in_array(5, $details['steps_finished']) === false)
                Form hasn't been submitted yet by merchant for activation
            @else
                    Active <br/>
                    <a 
                    href="{{{$details['merchant']['id']}}}/deactivate?_token={{{csrf_token()}}}" 
                    onclick="if (confirm('Are you sure you want to deactivate this merchant?')){return true;} return false;">
                    Deactivate Merchant
                    </a>
            @endif
            </h4>
            <br/><br/>

            <h4>Form Locked Status:</h4>
            @if($details['locked']) 
            Locked <a href="{{{$details['merchant']['id']}}}/unlock?_token={{{csrf_token()}}}">Unlock Form for user</a>
            @else
            Unlocked <a href="{{{$details['merchant']['id']}}}/lock?_token={{{csrf_token()}}}">Lock Form for user</a>
            @endif
            <br/><br/>

            <h4><a href = "{{{$details['merchant']['id']}}}/details">Check Activation Form Details</a></h4>
            <p>
            Note:
            <li>Check all activation form details, before activating the merchant.</li>
            <li>Lock the form once you have validated all steps to prevent changes by merchant.</li>
            <li>Unlock the form before you request changes from the merchant.</li>
            </p>
        </div>
    </div>
@stop