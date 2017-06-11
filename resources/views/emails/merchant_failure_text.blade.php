Hi,

This is to let you know that a payment failed.

Date: {{\Carbon\Carbon::createFromTimestamp($payment['captured_at'], "Asia/Kolkata")->format('jS F Y')}}
Time: {{\Carbon\Carbon::createFromTimestamp($payment['captured_at'], "Asia/Kolkata")->format('g:i a T (P)')}}

Payment Id:         {{$payment['public_id']}}
Amount:             {{$payment['amount']}}

Failure Reason:     {{$payment['error_description']}}

Customer Details:

- Email:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

You can view more payment details on the Merchant Dashboard [0].

[0]: https://dashboard.razorpay.com/#/app/payments/{{$payment['public_id']}}

--
Team Razorpay
