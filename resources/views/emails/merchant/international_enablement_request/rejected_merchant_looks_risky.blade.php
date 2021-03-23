@extends('emails.merchant.international_enablement_request.base')

@section('content')
    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
        We regret to inform you that the request has not been approved by our banking partners and hence we would not be able to enable international transactions for your account.
    </p>

    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
        In the meanwhile, we would like to inform you that you can use the <b>PayPal wallet to accept International payments</b>.
    </p>

    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px auto 5px;">
        You can <b>enable PayPal in three easy steps</b>:
    </p>

    <ol class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 5px auto 20px;">
        <li><a href="https://bit.ly/34uUhel">Login</a> to your Razorpay dashboard</li>
        <li>Go to Settings (on the left side panel)</li>
        <li>Find the PayPal section under Configuration, click on Link Account, and complete your activation with PayPal.</li>
    </ol>

    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
        That’s it, we’ll have PayPal live for your business within 48 hours.
    </p>

    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
        <b>Click <a href="https://bit.ly/2QpbMVq">here</a> to enable PayPal now.</b>
    </p>

    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
        <b>Have an existing PayPal account?</b> No problem! Just follow the steps above and link your account on Razorpay.
    </p>
@endsection
