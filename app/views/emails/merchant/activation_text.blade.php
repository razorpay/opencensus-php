Hi,

Your Razorpay account for {{{$merchant['billing_label']}}} is now active. You can now start accepting payments from your customers. You have opted for the {{{$plan['name']}}} plan.

The plan grants you the following rates:

@foreach ($rules as $pricing => $methodDisplay)
- {{implode(',', $methodDisplay)}} - {{$pricing}}
@endforeach

- +1% Extra on International Transactions
- Service Taxes Extra (14.5% currently)

In case you haven't integrated our API in your application, the instructions can be found at https://docs.razorpay.com.

If you face any issues while implementing this, feel free to drop us an support@razorpay.com.

We hope that the association between you and Razorpay will be fruitful for your organization.

Regards,
Team Razorpay
