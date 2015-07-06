Hi,

This is just a confirmation email to let you know that your payment was successful.

Date: {{date('jS F Y', $payment['timestamp'])}}
Time: {{date('g:h T (P)', $payment['timestamp'])}}

@if($merchant['billing_label'])
Website:    {{$merchant['billing_label']}}
Link:       {{$merchant['website']}}
@endif

Payment Id:         {{$payment['id']}}
Amount:             {{$payment['amount']}} Rupees
Payment Method:     {{ucwords($payment['method'][0])}}
Payment Details:    {{$payment['method'][1]}}

Customer Details:

- EMail:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

If this is correct, you don't need to take any further action.

You can contact us at contact@razorpay.com in case of any discrepancy.

--
Team Razorpay
