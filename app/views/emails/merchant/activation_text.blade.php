Hi,

Your Razorpay account for {{{$merchant['billing_label']}}} is now active. You can now start accepting payments from your customers.

The pricing details associated with your account are:

@foreach ($rules as $pricing => $methodDisplay)
- {{implode(',', $methodDisplay)}} - {{$pricing}}
@endforeach
@if ($merchant['international'] === true)
- 3% on International Transactions
@endif
- Service Taxes Extra (14.5%)

In case you haven't integrated our API in your application, the instructions can be found at https://docs.razorpay.com.

If you face any issues while implementing this, feel free to drop us an email at support@razorpay.com.

We hope that the association between you and Razorpay will be fruitful for both organizations.

Regards,
Team Razorpay
