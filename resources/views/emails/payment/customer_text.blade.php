Hi,

This is just a confirmation email to let you know that your payment was successful.

Date: {{\Carbon\Carbon::createFromTimestamp($payment['timestamp'], "Asia/Kolkata")->format('jS F Y')}}
Time: {{\Carbon\Carbon::createFromTimestamp($payment['timestamp'], "Asia/Kolkata")->format('g:i a T (P)')}}

@if($merchant['billing_label'])
Website:    {{$merchant['billing_label']}}
Link:       {{$merchant['website']}}
@endif

Payment Id:         {{$payment['public_id']}}
Amount:             {{$payment['amount']}}
Payment Method:     {{ucwords($payment['method'][0])}}
Payment Details:    {{$payment['method'][1]}}

Customer Details:

- EMail:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

If this is correct, you don't need to take any further action.

{{$data['support_text_plain']}}

--
Team Razorpay
