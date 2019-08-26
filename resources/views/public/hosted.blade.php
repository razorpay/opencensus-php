<!doctype html>
<html>
  <head>
    <title>Payment Page · Razorpay</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    <?php if ($_SERVER['HTTP_HOST'] !== "api.razorpay.com"): ?>
    <script>
    var Razorpay = {
      config: {
        api: '/'
      }
    };
    </script>
    <?php endif; ?>
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
  </head>
  <body onload="showCheckout()">
    @include('partials.loader')
    <div id="success" class="card">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.959 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591z"/></svg>
      <h3>Payment Successful!</h3>
      Just a moment now...
    </div>
    <form style="visibility: hidden" method="post" action="@html_attr($url_callback)" target="_self"></form>
    <script>
      var form = document.forms[0];
      var options = {!! $options !!};
      var urls    = {!! $urls !!};

      @if ($retry)
      if (window.btoa) {
        try {
          var data = btoa(JSON.stringify({
            request: {
              url: form.action,
              method: form.method,
              target: '_self'
            },

            options: JSON.stringify(options),

            back: urls.cancel
          }));

          options.callback_url =  location.protocol + '//' + location.hostname + '/v1/checkout/onyx?data=' + data;
        } catch(e){}
      }
      @else

      options.callback_url = urls.callback;
      @endif

      options.handler = function(data) {
        document.querySelector('#success').style.display = 'block';
        var h = '';
        for (var i in data) {
          h += '<input name="' + i + '" value="' + data[i] + '">';
        }
        form.innerHTML = h;
        form.submit();
      };

      if (!options.modal) {
        options.modal = {};
      }

      if (!('escape' in options.modal)) {
        options.modal.escape = false;
      }

      if (!('confirm_close' in options.modal)) {
        options.modal.confirm_close = true;
      }

      if (!options.theme) {
        options.theme = {};
      }

      if (urls.cancel) {
        options.modal.ondismiss = function() {
            location.href = urls.cancel;
        }
      }
      else {
        options.theme.close_button = false;
      }

      // darker shade, because there is nothing behind backdrop
      if (!options.theme.backdrop_color) {
        options.theme.backdrop_color = 'rgba(0, 0, 0, 0.8)';
      }

      var razorpay = Razorpay(options);
      function showCheckout() {
        razorpay.open();
        document.querySelector('.loader').style.display = 'none';
      }
    </script>
  </body>
</html>
