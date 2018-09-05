Hey,

Thank you for your patience.


@if ($reason_category === 'unsupported_business_model')

We have reviewed your application for subscription activation for your account {{$merchant_id}}.
Unfortunately, your current business model is not supported for {{$feature}} and hence, we would not be
able to enable/activate the feature at the moment.

@elseif ($reason_category === 'invalid_use_case')

We have reviewed your application for {{$feature}} activation for your account {{$merchant_id}}.
Unfortunately, your current use-case is not in accordance with our product and hence, we will not be
able to approve this request at the moment.

@else

We have reviewed your application for {{$feature}} activation for your account {{$merchant_id}}.
Unfortunately, we would not be able to enable/activate the feature at the moment.

We will reach out to you once we can support {{$feature}} for your business.

@endif

If you have any queries, please reach out to us <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a>

Regards,
Team Razorpay
