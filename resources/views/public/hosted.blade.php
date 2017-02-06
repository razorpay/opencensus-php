<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    <script src="{{{ $checkout }}}"></script>
    <style>
      body {
        text-align: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
        color: #414141;
        background: #ecf0f1;
      }
      path {
        fill: #6DCA00;
      }
      h3 {
        font-weight: normal;
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
      a {
        display: block;
        margin: 30px 0 10px;
        color: #09f;
      }
      #success {
        display: none;
      }
    </style>
    <script>
      var options = {!! $options !!};

      options.handler = function() {
      }

      if (!options.modal) {
        options.modal = {};
      }

      options.modal.escape = false;
      options.modal.confirm_close = true;
      options.modal.ondismiss = function() {
      }

      function showCheckout() {
        Razorpay.open(options);
      }
    </script>
  </head>
  <body onload="showCheckout()">
    @include('partials.loader')
    <div id="success" class="card">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.959 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591z"/></svg>
      <h3>Payment Successful!</h3>
    </div>
  </body>
</html>
