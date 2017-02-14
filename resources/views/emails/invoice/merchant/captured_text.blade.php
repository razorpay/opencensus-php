Hi,

This is just a confirmation email to let you know that an invoice was successfully paid.

Date: {{\Carbon\Carbon::createFromTimestamp($invoice['paid_at'], "Asia/Kolkata")->format('jS F Y')}}
Time: {{\Carbon\Carbon::createFromTimestamp($invoice['paid_at'], "Asia/Kolkata")->format('g:i a T (P)')}}

Payment Id:         {{$invoice['payment_id']}}
Invoice Id:         {{$invoice['public_id']}}
Amount:             {{$invoice['amount']}}

Customer Details:

- Email:    {{$customer['email']}}
- Contact:  {{$customer['phone']}}

If this is correct, you don't need to take any further action.

You can view more invoice details on the Merchant Dashboard [0].

[0]: https://dashboard.razorpay.com/#/app/invoices/{{$invoice['public_id']}}

--
Team Razorpay
