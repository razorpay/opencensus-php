@extends('emails.merchant.risk.alert_funds_on_hold')

@section('content')
	Dear {{$merchant_name}},
	<br/><br/>
	Hope you are doing fine. We wanted to write to you as we noticed an issue with your website. 
	<br/><br/>
	We have noticed that your registered website(s) is(are) not operating at the moment. Please clarify your current business website URL. Also please confirm to us how payments are being collected currently.
	<br/><br/>
	As per the regulatory guidelines, we will need to place your settlements under review, in case we don’t receive the clarification for the observation within {{$days_to_foh}} days. This check is towards protecting the interests of businesses and customers.  
	<br/><br/>
	Look forward to hearing from you. Your response will help us to provide you with a seamless experience. Please stay safe in these challenging times.
	<br/><br/>
	Warm regards,
	<br/>
	Team Razorpay
@endsection
