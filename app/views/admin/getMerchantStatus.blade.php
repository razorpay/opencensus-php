@extends('admin.layoutGenerated')

@section('panelcontent')
    <div class="centered form-wrapper">
        <div class="content">
            <h2>Merchant Activation Details</h2>
            <h4>Merchant Id: {{{$details['merchant']['id']}}}</h4>
            <h4>Email: {{{$details['merchant']['email']}}}</h4>
            <h4>Live: {{{$details['merchant']['live']}}}</h4><br/>
            <h4>Steps Finished by Merchant</h4>
            @foreach($details['steps_finished'] as $step)
                <input type="checkbox" name="steps_finished[]" value="{{$step}}" checked> Step {{$step+1}}
            @endforeach
            <br/><br/>
            <h4>Submission Status:</h4>
            @if(in_array(5, $details['steps_finished']))
                Submitted for Activation
            @else
                Form hasn't been submitted yet by merchant for activation
            @endif
            <br/><br/>

            <h4>Form Locked Status:</h4>
            @if($details['locked']) 
            Locked <a href="unlock?token={{{csrf_token()}}}">Unlock Form for user</a>
            @else
            Unlocked <a href="lock?token={{{csrf_token()}}}">Lock Form for user</a>
            @endif
            <br/><br/>

            @if($details['merchant']['live'])
                <h4><a href="deactivate?token={{{csrf_token()}}}">Deactivate Merchant</a></h4>
            @else
                If everything is good, click below to activate him and fill pricing and TID details
                <h4><a href="activate">Activate Merchant</a></h4>
            @endif

            <br/><br/>
            <h4><a href = "/admin/merchant/{{{$details['merchant']['id']}}}/details">Check Activation Form Details</a></h4>

            <p>
            Note:
            <li>Check all activation form details, before activating the merchant.</li>
            <li>Lock the form once you have validated all steps to prevent changes by merchant.</li>
            <li>Unlock the form before your request changes from the merchant.</li>
            </p>
        </div>
    </div>
@stop