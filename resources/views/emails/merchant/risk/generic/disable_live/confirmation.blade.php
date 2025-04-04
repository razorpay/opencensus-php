@extends('emails.merchant.risk.alert_funds_on_hold')

@section('content')
    Dear {{$merchant_name}},
    <br/><br/>
    Greetings from Razorpay!
    <br/><br/>
    We are writing to let you know we have disabled your account. This means that you will no longer be able to accept new transactions and there will be a temporary hold on any settlements that are due.
    <br/><br/>
    It was necessary to do so because of a risk alert for non-compliance with regulatory guidelines as set by one of our partner banks.
    <br/><br/>
    If you have any questions and wish to reach out to us with urgency, please call <b style="color: #3b6790 !important;">08068838200</b>, using your ongoing ticket ID as the PIN. Our team is available daily from 10 AM to 7 PM.
    <br/><br/>
    Thank you for your prompt attention to this matter.
    <br/><br/>
    Sincerely,
    <br/>
    Team Razorpay
@endsection
