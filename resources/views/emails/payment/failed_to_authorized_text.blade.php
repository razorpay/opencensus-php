Hi,

This is just a confirmation email to let you know that a payment was successful.

This payment (earlier marked as failed) has now been converted to authorized.

This means that money was deducted from the customer's account. Please capture this payment and process it immediately.

Date: {{\Carbon\Carbon::createFromTimestamp($payment['captured_at'], "Asia/Kolkata")->format('jS F Y')}}
Time: {{\Carbon\Carbon::createFromTimestamp($payment['captured_at'], "Asia/Kolkata")->format('g:i a T (P)')}}

Payment Id:         {{$payment['public_id']}}
Amount:             {{$payment['amount']}}

Customer Details:

- EMail:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

If this is correct, you don't need to take any further action.

You can view more payment details on the Merchant Dashboard [0].

[0]: https://dashboard.razorpay.com/#/app/payments/{{$payment['public_id']}}

--
Team Razorpay
