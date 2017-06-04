Hi,

Your {{{$merchant['org']['business_name']}}} account for {{{$merchant['billing_label']}}} is now active. You can now start accepting payments from your customers.

The pricing details associated with your account are:

@if(count($rules['amountRangeRules']) === 2)
- {{$rules['amountRangeRules']['low']}}
- {{$rules['amountRangeRules']['high']}}
@endif
@foreach ($rules['otherRules'] as $pricing => $methodDisplay)
- {{implode(', ', $methodDisplay)}} - {{$pricing}}
@endforeach
- Service Taxes Extra (15%)

In case you haven't integrated our API in your application, the instructions can be found at https://docs.razorpay.com.
Please ensure that your production website/app is using the live keys generated from the dashboard.

If you face any issues while implementing this, feel free to drop us an email at support@razorpay.com.

We hope that the association between you and {{{$merchant['org']['business_name']}}} will be fruitful for both organizations.

Regards,
Team {{{$merchant['org']['business_name']}}}
