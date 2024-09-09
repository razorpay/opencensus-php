@extends('emails.merchant.risk.alert_funds_on_hold')

@section('content')
	Dear {{$merchant_name}},
	<br/><br/>
    We hope this email finds you well.
	<br/><br/>
    To maintain the integrity of our platform and ensure compliance with regulatory standards, Razorpay conducts periodic reviews of merchant accounts. As part of this process, we have identified potential risk indicators associated with your account.
    <br/><br/>
    To resolve this quickly and resume your settlements, we kindly request the following documents:
    <br/>
    1. <b>Detailed invoices: </b>Please provide invoices for recent successful transactions.
    <br/>
    2. <b>Proof of delivery: </b>Provide evidence of delivery for at least three customers, such as email confirmations from customers or shipping tracking information.
    <br/>
    3. <b>Additional Documentation: </b>Any other supporting documents that validate your business activities.
    <br/><br/>
    Please note that this is a standard procedure implemented across all merchant accounts to ensure platform security. Once we receive the requested documents, we’ll prioritize your account review and aim to conclude the case within 48-72 hours from the time of your last submission. If additional information is needed, we will promptly reach out to you.
    <br/><br/>
    A Razorpay representative will contact you tomorrow on your registered mobile number to discuss this further and answer any questions you may have.
    <br/><br/>
    Thank you for your prompt attention to this matter.
	  <br/><br/>
    Sincerely,
    <br/>
	The Razorpay Team
@endsection
