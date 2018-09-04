@if((isset($type) === true) and ($type === 'customer'))
  Read our customer <a href="https://razorpay.com/dispute-guide/">dispute resolution policy</a>.
  For further assistance, you can reach out to us
  <a href="https://razorpay.com/contact/">here</a>.
@elseif((isset($type) === true) and ($type === 'merchant_transaction'))
  For chargeback queries you can read our <a href="https://razorpay.com/chargeback/">chargeback</a> guide.
  You can reach out to us <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a>
  for any other assistance.
@else
  You can reach out to us <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a>
  for any further assistance.
@endif