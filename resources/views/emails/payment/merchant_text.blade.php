Hi,

This is just a confirmation email to let you know that a payment was successful.

Date: {{\Carbon\Carbon::createFromTimestamp($payment['captured_at'], "Asia/Kolkata")->format('jS F Y')}}
Time: {{\Carbon\Carbon::createFromTimestamp($payment['captured_at'], "Asia/Kolkata")->format('g:i a T (P)')}}

Payment Id:         {{$payment['public_id']}}
Amount:             {{$payment['amount']}}

Customer Details:

- Email:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

If this is correct, you don't need to take any further action.

You can view more payment details on the Merchant Dashboard [0].

[0]: https://dashboard.razorpay.com/#/app/payments/{{$payment['public_id']}}

--
Team Razorpay
