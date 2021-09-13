@extends('emails.merchant.risk.alert_funds_on_hold')
@section('content')
Dear {{$merchant_name}},
<br/> <br/>
We have received a risk alert against your account for non-compliance with regulatory guidelines as set by one of our partner banks. To resolve this issue for you on priority, please share some sample invoices of the recent successful payments.
<br/> <br/>
<b>Please note that you can continue to receive payments from your customers and your Razorpay payments experience will not be hampered</b>. However, delays in sending the requested details could lead to a temporary hold on settling collected payments into your account.
<br/> <br/>
We assure you of our best service and support. Request your cooperation to resolve this as soon as possible.
<br/> <br/>
Thanks,
<br/>
Team Razorpay
@endsection
