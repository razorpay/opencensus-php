@extends('emails.merchant.risk.alert_funds_on_hold')

@section('content')
	Hello Team,
	<br/><br/>
	As a part of our financial risk measures, we regularly receive alerts from banks and regulatory authorities on potentially fraudulent transactions. Our systems also screen account activity to flag off similar concerns.
	<br/><br/>
	As a part of this exercise, we have come across a suspicious account activity and have highlighted the same in our previous email. We observed that the mobile application URLs shared with Razorpay are no longer live.
	<br/><br/>
	As per the regulatory guidelines, we have to put your settlements under review. Please respond with clarification on the same to resume settlements. This check is towards protecting the interests of businesses and customers.
	<br/><br/>
	Rest assured, your funds are in safe custody and will be settled post the review.
	<br/><br/>
	Thank you for your cooperation.
	<br/><br/>
	Warm regards,
	<br/>
	Team Razorpay
@endsection
