Hi,

Your Razorpay account for {{{$merchant['billing_label']}}} is now active. You can now start accepting payments from your customers. You have opted for the {{{$plan['name']}}} plan.

The plan grants you the following rates:

@foreach ($plan['rules'] as $rule)
{{--Cards--}}
@if ($rule['payment_method'] === 'card')
@if ($rule['payment_network'] !== null)
- {{{$rule['payment_network_name']}}} Cards - {{{$rule['display']}}}
@else
- {{{isset($rule['payment_method_type']) ? ucfirst($rule['payment_method_type']) : 'All'}}} Cards - {{{$rule['display']}}}
@endif
{{--Net Banking --}}
@elseif ($rule['payment_method'] === 'netbanking')
- Net Banking - {{{$rule['display']}}}
{{--Wallet--}}
@elseif ($rule['payment_method'] === 'wallet')
- Wallets - {{{$rule['display']}}}
@endif
@endforeach
- +1% Extra on International Transactions
- Service Taxes Extra (14.5% currently)

In case you haven't integrated our API in your application, the instructions can be found at https://docs.razorpay.com.

If you face any issues while implementing this, feel free to drop us an support@razorpay.com.

We hope that the association between you and Razorpay will be fruitful for your organization.

Regards,
Team Razorpay
