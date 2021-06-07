@extends('emails.merchant.risk.alert_funds_on_hold')

@section('content')
	Dear {{$merchant_name}},
	<br/><br/>
	Greetings from Razorpay!
	<br/><br/>
	We have temporarily put your settlements under review. It was necessary to do so because of a risk alert for non-compliance with regulatory guidelines as set by one of our partner banks.
	<br/><br/>
	To resume settlements as soon as possible, please share some sample invoices of the recent successful payments.
	<br/><br/>
	We assure you of our best service and support. We request your cooperation to resolve this as soon as possible.
	<br/><br/>
	Thanks,
	Team Razorpay
@endsection
