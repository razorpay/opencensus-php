Hi,

This is just a confirmation email to let you know that your payment was successful.

Date: {{\Carbon\Carbon::createFromTimestamp($payment['timestamp'], "Asia/Kolkata")->format('jS F Y')}}
Time: {{\Carbon\Carbon::createFromTimestamp($payment['timestamp'], "Asia/Kolkata")->format('g:i a T (P)')}}

@if($merchant['billing_label'])
Website:    {{$merchant['billing_label']}}
Link:       {{$merchant['website']}}
@endif

Payment Id:         {{$payment['public_id']}}
Amount:             {{$payment['gateway_amount_spread'][0]}} {{$payment['gateway_amount_spread'][1]}}.{{$payment['gateway_amount_spread'][2]}}
Payment Method:     {{ucwords($payment['method'][0])}}
Payment Details:    {{$payment['method'][1]}}

@if($payment['dcc'] === true)
Base Amount:        {{$payment['dcc_base_amount']}}
Fees:               {{$payment['currency_conversion_fee']}}
Total Amount:       {{$payment['gateway_amount']}}

The cost of currency conversion as they may be different depending on whether you select your home currency or the transaction currency.
@endif

Customer Details:

- EMail:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

If this is correct, you don't need to take any further action.

@if((isset($data) === true) and (isset($data['support_text_plain']) === true))
{{$data['support_text_plain']}}
@else
You can contact us at https://razorpay.com/contact/ in case of any discrepancy.
@endif

--
Team Razorpay
