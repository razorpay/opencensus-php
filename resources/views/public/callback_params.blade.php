<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    <style>
      body {
        text-align: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
        color: #414141;
        background: #ecf0f1;
      }
      .card {
        background: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 9px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin: 30px auto;
        width: 80%;
        max-width: 300px;
      }
      path {
        fill: #EF6050;
      }
      button {
        background-color: #4994E6;
        color: #fff;
        border: 0;
        outline: none;
        cursor: pointer;
        font: inherit;
        margin-top: 10px;
        padding: 10px 20px;
        border-radius: 2px;
      }
      button:active {
        box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset, 0 0 6px rgba(0,0,0,.2) inset;
      }
      a {
        display: block;
        margin: 30px 0 10px;
        color: #09f;
      }
    </style>
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
      <div class="card">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>
        <h2>Payment Failed</h2>
        <p><b>Error:</b> {{ $error }}</p>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
          var options = {!! $options !!};
          options.callback_url = location.href;
          var rp = Razorpay(options);
          rp.on('payment.success', function() {
            document.querySelector('.card').innerHTML = '<h2>Payment Successful.</h2><p>Just a moment now...</p>';
          })
        </script>
        <button onclick="rp.open()">Retry Payment</button>
        <a href="{{ $back }}">← Go back to website</a>
      </div>
    @endif
  </body>
</html>
