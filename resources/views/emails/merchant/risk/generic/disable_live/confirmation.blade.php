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
    There will be a hold on your settlements that are due for 120 days.
    <br/><br/>
    If you believe there has been an error, please reply to this email and we will review your case.
    <br/><br/>
    Stay safe.
    <br/><br/>
    Thanks,
    <br/>
    Team Razorpay
@endsection
