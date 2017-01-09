<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
  </head>
  <body @if (isset($payment_id)) onload="document.forms[0].submit()" @endif>
    @if (isset($payment_id))
      @include('partials.loader')
      <form method="post" action="{{{ $url }}}">
        @foreach ($params as $key => $value)
          <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}">
        @endforeach
        <input type="hidden" name="razorpay_payment_id" value="{{{ $payment_id }}}">
      </form>
    @else
      <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
      <script>
        var rp = Razorpay({!! $options !!});
      </script>
      <button onclick="rp.open()"></button>
    @endif
  </body>
</html>
