@extends('emails.merchant.risk.alert_funds_on_hold')

@section('content')
	Dear {{$merchant_name}},
	<br/><br/>
	Greetings from Razorpay!
	<br/><br/>
    We have temporarily put your settlements under review. It was necessary to do so because of a risk alert raised by our banking partners.
    <br/><br/>
    To resume the settlements as soon as possible, we request you to provide one of the following documents confirming recent successful transactions on Razorpay:
    <br/>
    1. Detailed invoices
    <br/>
    2. Proof of delivery
    <br/>
    3. Any other proof confirming the above
	<br/><br/>
    The check is needed to validate the services provided to your customers.
    <br/><br/>
    We expect a response within 48-72 hours which will be thoroughly reviewed by our team. If found satisfactory, we will close the loop within 72 hours.
    <br/><br/>
    Incase of any questions  please  reach out to us at risk-fundsonhold@razorpay.com . We request your cooperation to resolve this as soon as possible.
    <br/><br/>
	Thanks,
	<br/>
	Team Razorpay
@endsection
