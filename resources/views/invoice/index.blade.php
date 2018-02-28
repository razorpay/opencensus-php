<!doctype html>
<html>
  <head>
    <title>Invoice</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet" type="text/css"></link>
    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />
    <?php
      $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';
    ?>

    @if (isset($data['environment']))
      @if ($data['environment'] !== 'production')
        <script>
          var Razorpay = {
            config: {
              api: '/'
            }
          }
        </script>
      @endif
    @endif
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
          box-sizing: border-box;
        }
      body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Lato', 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
        color: #414141;
        background: #fff;
      }

      #success path {
        fill: #6DCA00;
      }

      #failure path {
        fill: #e74c3c;
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
        text-align: center;
      }

      #break {
        color: #777;
        font-size: 14px;
        margin: 30px -30px 0;
        padding: 30px 30px 0;
        border-top: 1px dashed #e3e4e6;
        text-align: left;
        line-height: 24px;
      }

      #break span {
        float: right;
      }

      #success {
        display: none;
      }

      .paid #success {
        display: block;
      }

      .issued #partial {
        display: none;
      }

      #button {
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

      #button:active {
        box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset, 0 0 6px rgba(0,0,0,.2) inset;
      }

      body div.redirect-message {

        display: none;
      }

      body.has-redirect div.redirect-message {

        display: block;
      }

      #custom-container {
        width: 100%;
      }

      #payment-container {
        width: 100%;
        position: relative;
        max-width: 880px;
        margin: 20px auto 0;
      }

      .table-box {
        display: inline-block;
        vertical-align: middle;
      }

      .table-box > div {
        min-width: 350px;
      }

      #inv-info-par {
        max-width: 600px;
        width: 60%;
      }

      #chkout-par {
        width: 39%;
        max-width: 350px;
      }

      #chkout-box {
        width: 100%;
        margin: 0 auto;
        margin-left: -15px;
        box-shadow: 0px 0px 20px rgba(0,0,0,0.08);
        min-height: 511px;
        background-color: #fff;
        overflow: hidden;
        z-index: 0;
      }

      #overlay {
        position: fixed;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        background-color: rgba(0, 0, 0, 0.05);
        opacity: 0;
        z-index: 0;
        transition: 0.5s all ease-in-out;
      }

      #payment-container iframe.razorpay-checkout-frame {
        min-height: 511px !important;
      }


      #inv-info-box {
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
        box-shadow: 0px 0px 20px rgba(0,0,0,0.08);
        border: 1px solid #dfdfdf;
        border-radius: 4px;
      }

      .inv-details {
        padding: 30px 40px;
        background-color: #fff;
      }

      .inv-details .inv-for {
        font-size: 16px;
        font-weight: 500;
      }

      .inv-details {
        font-size: 14px;
      }

      .inv-details .info {
        color: #747474;
        margin-top: 16px;
        line-height: 22px;
        font-size: 13px;
      }

      .inv-details .info .val {
        color: #232323;
        font-size: 14px;
        text-transform: capitalize;
      }

      .inv-details .info .amount {
        font-weight: 600;
        font-size: 24px;
      }

      .inv-details .amount:after {
        content: '';
        display: block;
        width: 18px;
        border-bottom: 2px solid #18bd5a;
        margin-top: 12px;
      }

      #inv-info-box .footer {
        background-color: #fafafa;
        padding: 20px 40px;
        border-bottom-right-radius: 4px;
        border-bottom-left-radius: 4px;
        color: #717171;
        font-size: 12px;
      }

      #inv-info-box .footer img {
          height: 15px;
          vertical-align: bottom;
      }

      #footer {
        margin: 28px auto;
        max-width: 655px;
        width: 85%;
        padding: 15px 24px;
        background-color: #fcfcfc;
        border: 1px solid #dfdfdf;
        font-size: 12px;
        border-radius: 4px;
        box-shadow: 0px 0px 15px rgba(0,0,0,0.08);
        color: #787878;
        overflow: auto;
      }

      #footer a {
        color: #8a8a8a;
      }

      #footer img {
          height: 24px;
          margin-bottom: 4px;
      }
      #footer #rzp-logo {
        float: right;
      }

      .bg-svg {
          position: absolute;
          z-index: -100;
          left: -330px;
          top: -35px;
      }


    </style>
  </head>
  <body>

    <script>

      (function (globalScope) {

        var data = {!!utf8_json_encode($data)!!};

        function forEach (dict, cb) {

          dict = dict || {};

          if (typeof dict !== "object" || typeof cb !== "function") {

            return dict;
          }

          var key, value;

          for (key in dict) {

            if (!dict.hasOwnProperty(key)) {

              continue;
            }

            value = dict[key];
            cb.apply(value, [value, key, dict]);
          }

          return dict;
        }

        function parseQuery(qstr) {

          var query = {};

          var a = (qstr[0] === '?' ? qstr.substr(1) : qstr).split('&'), i, b;

          for (i = 0; i < a.length; i++) {

            b = a[i].split('=');
            query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
          }

          return query;
        }

        function createHiddenInput (key, value) {

          var input = document.createElement("input");

          input.type  = "hidden";
          input.name  = key;
          input.value = value;

          return input;
        }

        function hasRedirect () {

          return data.invoice &&
                 data.invoice.callback_url &&
                 data.invoice.callback_method;
        }

        function redirectToCallback (callbackUrl,
                                     callbackMethod,
                                     requestParams) {

          document.body.className = ([document.body.className,
                                      "paid",
                                      "has-redirect"]).join(" ");

          var form   = document.createElement("form"),
              method = callbackMethod.toUpperCase(),
              input, key;

          form.method = method;
          form.action = callbackUrl;

          forEach(requestParams, function (value, key) {

            form.appendChild(createHiddenInput(key, value));
          });

          var urlParamRegex = /^[^#]+\?([^#]+)/,
              matches       = callbackUrl.match(urlParamRegex),
              queryParams;

          if (method === "GET" && matches) {

            queryParams = matches[1];

            if (queryParams.length > 0) {

              queryParams = parseQuery(queryParams);

              forEach(queryParams, function (value, key) {

                form.appendChild(createHiddenInput(key, value));
              });
            }
          }

          document.body.appendChild(form);

          form.submit();
        }

        globalScope.data               = data;
        globalScope.hasRedirect        = hasRedirect;
        globalScope.redirectToCallback = redirectToCallback;
      }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>

    @if (isset($data['error']))
      <div id="failure" class="card">
        {!! $error_icon !!}
        <h2>Error</h2>
        <p>{{$data['error']['description']}}. Please contact the merchant for assistance.</p>
      </div>
    @else
      <div id="invoice-status-container" class={{$data['invoice']['status']}}>
          @if (isset($data['invoice']) && $data['invoice']['type'] !== 'invoice')
              <div id="custom-container">
                  <div id="payment-container">
                    <svg class="bg-svg" width="1665px" height="665px" viewBox="0 0 1665 665" preserveAspectRatio="none">
                        <polygon fill="#fafafa" transform="translate(750, 300) scale(1, -1) translate(-810, -274.0)" points="0 200 1665 3 1665 346 117 545 0 475"></polygon>
                        <polygon fill="#f5f5f5" transform="translate(0, -40)" points="-40 200 1465 0 1665 450 1300 670 0 635"></polygon>
                    </svg>

                      <div class="table-box" id="inv-info-par">
                          <div id="inv-info-box">
                              <div class="inv-details">
                                  <div class="inv-for">Payment requested by {{$data['merchant']['organization']['business_name']}}</div>
                                  <div class="info">
                                      PAYMENT FOR
                                      <div class="val">{{$data['invoice']['description']}}</div>
                                  </div>

                                  <div class="info">
                                      REQUEST EXPIRES
                                      <div class="val">{{$data['invoice']['expire_by']}}</div>
                                  </div>

                                  <div class="info">
                                      AMOUNT PAYABLE
                                      <div class="val amount">₹{{number_format($data['invoice']['amount_due']/ 100, 2, '.', ',')}}</div>
                                  </div>
                              </div>
                              <div class="footer">
                                  Powered by
                                  <img src="https://cdn.razorpay.com/logo.svg" />
                              </div>
                          </div>
                      </div>
                      <div class="table-box" id="chkout-par">
                        <div id="overlay"></div>
                        <div id="chkout-box" onmouseout="hideOverlay()" onmouseover="showOverlay()"></div>
                      </div>
                  </div>
                  <div id="footer">
                      <div>
                          <img style="padding:3px 0" src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDACgcHiMeGSgjISMtKygwPGRBPDc3PHtYXUlkkYCZlo+AjIqgtObDoKrarYqMyP/L2u71////m8H////6/+b9//j/2wBDASstLTw1PHZBQXb4pYyl+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj/wAARCAAoAWoDASIAAhEBAxEB/8QAGgAAAgMBAQAAAAAAAAAAAAAAAAQBAwUCBv/EADMQAAICAQIFAgQEBwADAAAAAAECAAMRBCEFEjFBURMUImFxgTJTkaFCQ1JisdHhM3Lw/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAHxEAAgICAgMBAAAAAAAAAAAAAAECERIhAzETQVFh/9oADAMBAAIRAxEAPwDZhFNRoUsUmtnR+2GOJncM1Fi6xUZ2KtkEEylG0BuQiHFtQaqBWpIZz28RfSc9XDr9Q7sSwwuT9v8AMMdWBrwmLwpmN1ljuxVEzuZU1+p1+o5FblB6LnAEeGwN+EyKOHaqrUVsXHKGBblY9I9r9R7fSswOGOy/WKt6AZhPM132pYjl3OCDuTvPSg5GRCUaAmVai9dPUXbfwPJlsyOKuTqAnZVkSdI04oZyplN2sutO7lR4XYTiq66sj03YfLO0rllAy5PgTFv2ehjFKqNFdVY6gMQDjfHeQGYHIY5i6nDCXznk23ZzuKXQzTeSQr/rGJnR+tuatT8p0cM29M5+SNbRJ/CYt6j/ANRjLfhP0ivK3j951RMJENY4/iMpe+0dHMuZGPj9ZQ9TncAfqJoqMZ5eis6m78xo3oLXsL87E4xjMU9vYegHXH4hGeHoyPYGGDgQnVaDjyvY9IJkxbWsy1ZU4wwyZilbOhulZ3ZfXUcPYAfE6R1deZXyvmc10VpkhQxO5Y7kymsNzvpy2eQhlJ8eI6XoVu9jCuGzhth3MnI3+Lp3lPolfiBBIOcAbdT/ALnK0HCnKgg5wRt3/wBySmMZ/v8A3grqSQGBI64i502ebLD4pYqcljMMYbG2OkdIm2Xzh7ErGXYKD5M7lGtq9XTOB1G4iG+gOs04/mrOTr9MP5n7GYyqzHCqSfkJJqsAya2A+kqkZeRmseI6cfxMftOTxOjsHP2mTO1ptYZWtyPIEdIM5GieKV9q3McqsFtSuvRhMzh1i02OHRs+QucRvT6lX1NlYUqOoBGPrJaKjL6NwlbXIjBS3xHsBmKG3U2au2qqwKF3GREtlOSQ/CZ66y5tGzhR6itgkDt5k6TUPZcF9ZXUjcEYP2joMkPwmWdXdzsGtFbA7IV2/WaKMSik8uSOx2hQKSZ3PP3j23EyegDhh9Os9BMjjNLG2uxVJyMHAlQ7KF9Y51mv5U3GeRY5xXFGhrpXpkD7CVcI0x9VrnUjlGBkd4cY53vRVViFXsPMr2kIu4PUPaOzDZzj7RTU8Pu0zGyrLINww6iOMbtLwuoUqefYnAzjvFW4nqmUpyLk7ZCnMSu7QF/DeIPbYKbtyfwtF+LXerqhUp2Tb7ydHprKA2qtQjkB5VxuTKdNo7dXc3MSncsRHSuwLOI1V1pQK2VsLynB/wDvM1tFZ6mjqb+3B+20ytTwtqKTYtnPjsFjvCC3tmRgRyttkdopbiA/MvitRFi2gbEYP1mpObK1tQo4ypmTVo0454Ss87O6Ww/12jl3DLASamDDwdjOE4bex+LlUfXMycX0d/lg12Soywl0uGj5FAVsnvnvI9vZ4H6zCXHK+jnfJFlQGTgR9F5UA8CcVUivcnJls34uNx2zGcr6IY4UnwIkl6swAByem8cf/wAbfQzIR+Rw2M4M6oK0zl5ZNNDpfG4G3/tOSe+MY/u/5KPcAADk6fOR7gb5rU+PlLxIysv3zkrnf+rqf0+st0ikO5PTAHXPmJnUjBHJsTnrGtDYLHsPLg7d5Mk6NI1Y5K7AGDKRkEdJZMzXam2vUMiNgADtM0rZcnS2XIdSq+mqKANgzNnad11cj85YsxXBJ7xS1NQlRYu/wgE5YTlOZlrJY5c4GJpV+zPJr0O3V87IwUErnc/Tb95xjUDbmJ267dcf7i/KBklyANtxg5+kMN2c/h5osP0PK/hcRqNyCQTjO4Pb/c7rWz1iz77EdR5lBS1WwLW2Bzsc7fKUnV2oxAbIB7iLD4V5PqNmEISDQz9LV6PELV7cuR9MxoG/3RGB6OP3kXslDi9gcY5TgTM1Wqa20mt3CEdM4ldmbaiOpTTZr7GAB5AMjtmVavXW1XmusABfI6xTTahtPZzAZB2I8xw36PUuvqIQ52ydoUK7WtBw+w3am2xsZIGcSLVNessvPY4QeTj/ABO9M9NVr5X0zjGO0mpTqbzY34V6SHL4K7SS7LtLSVHqPu7ee0Wet01Nttd9ak7HPaPVV+mnLzM3fJkGitiSQdznqY1ovHVCS1JVpyiakLZnJYH9pKU5uW622v4c45B1xGvbU5zyCSdPUc5Xqc4yY7DERsrcqyNqK2Q75bdgIwmko5F+Jjt1yZcNNSOidsde0thY1H6EIQiKCEIQAIQhAAhCEACEIQAJVejuAEYr1zg47bfvCEAKWp1GMK5Pj4yMHA3/AMwNNxYkkkB+YfGem8IQAlKdRn47T1zsfkf+bSDVqMLhjt1+M9fP/IQgANRcQoLFuhOWOxzn77RuEIAcuMowHXEyva3/AJZhCVGTREoqXYe1v/LMPaX/AJZhCXmyfGiPaX/ln9Y5oKbKi/OpXOMQhJcm0UopMcmfxHSvYwtrGTjDAQhJTplSVoVt1l1lbVsgGcAkA52ldd71gBawMHJ2O8ITajG2QLTgr6Y5Scgb7GdrqXwAUGRgZx1xCEBE+4fGGQNkY3z5zOtJpXutDMpFYOST3hCKTpaHFW9mzCEJibld1YtpdD3Exq9HfYdqyPmdoQjTIlFNjdfC+9tn2WN16OirpWCfLbwhCxqKRF+lW5uYHlbvt1ltVYqrCDt38whJoeKTs7hCEYwhCEACEIQA/9k=" />
                          <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" />
                      </div>
                      <div>
                          Want to create payment links for your business? Visit
                          <a href="razorpay.com/payment-links" target="_blank">razorpay.com/payment-links</a>
                          and get started instantly
                      </div>
                  </div>
              </div>
          @endif
        @if ($data['invoice']['type'] !== 'invoice')
          <div id="success" class="card">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.959 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591z"/></svg>
            <h3>Your Payment has been received</h3>
            <div id='break'>
              <div>Amount Paid<span>₹ {{ $data['invoice']['amount']/100 }}</span></div>
              <div>Invoice ID<span>{{ $data['invoice']['id'] }}</span></div>
              <div>Payment ID<span id='pay_id'>{{ $data['invoice']['payment_id'] or '' }}</span></div>
            </div>
          </div>

          <div class="redirect-message">
            <br/>
            <center><i>Redirecting you to the Merchant Site...</i></center>
          </div>

          @if ($data['invoice']['partial_payment'] && $data['invoice']['amount_due'] > 0)
            <div id="partial" class="card">
              <h3>You have made a partial payment of ₹ {{ $data['invoice']['amount_paid']/100 }}.</h3>
              <button id="button" onclick="razorpay.open()">Pay remaining ₹ {{ $data['invoice']['amount_due']/100 }}</button>
              <div id='break'>
                <div>Amount Paid<span>₹ {{ $data['invoice']['amount_paid']/100 }}</span></div>
                <div>Amount Due<span>₹ {{ $data['invoice']['amount_due']/100 }}</span></div>
                <div>Total<span>₹ {{ $data['invoice']['amount']/100 }}</span></div>
                <div>Invoice ID<span>{{ $data['invoice']['id'] }}</span></div>
              </div>
            </div>
          @endif

          @if ($data['invoice']['status'] !== 'paid')
            @if (isset($data['error']))
              <div id="failure" class="card">
                {!! $error_icon !!}
                <h2>Payment Failed</h2>
                <p>{{ $data['error']['description'] }}</p>
                <button id="button" onclick="razorpay.open()">Retry</button>
              </div>
            @endif
            <script>
              (function (globalScope) {

                var data = globalScope.data;

                var invoiceObj = data.invoice;
                var merchant = data.merchant;

                var options = {
                  key: data.key_id,
                  invoice_id: invoiceObj.id,
                  amount: invoiceObj.amount,
                  parent: '#chkout-box',
                  description: 'Invoice #' + invoiceObj.id,
                  handler: function(response) {

                    if (globalScope.hasRedirect()) {

                      return globalScope.redirectToCallback(
                                                             data.invoice.callback_url,
                                                             data.invoice.callback_method,
                                                             response
                                                           );
                    }

                    if (invoiceObj.partial_payment && invoiceObj.amount_due) {
                      document.querySelector('#partial').style.display = 'block';
                      document.querySelector('#button').style.display = 'none';
                      document.querySelector('#partial h3').innerHTML = 'Please wait...';
                      return location.reload();
                    }
                    if (data.merchant && data.merchant.name) {
                      document.querySelector('#success h3').innerHTML = 'Thank you for your payment on ' + data.merchant.name;
                    }
                    document.querySelector('#pay_id').innerHTML = response.razorpay_payment_id;
                    document.body.className = 'paid';
                  },
                  prefill: {
                    contact: invoiceObj.customer_details.customer_contact,
                    email: invoiceObj.customer_details.customer_email,
                  },
                  callback_url: location.href,
                  image: 'https://i.imgur.com/n5tjHFD.png',
                  theme: {
                    close_button: false,
                    color: "#19be5c"
                  },
                  modal: {
                    confirm_close: true,
                    escape: false
                  }
                };
                @if (isset($data['merchant']))
                  @if ($data['merchant']['id'] === '6lGF5wNtCS8UA0')
                    options.theme.branding = 'payzapp'
                  @elseif (isset($data['merchant']['organization']))
                    @if (isset($data['merchant']['organization']['invoice_logo_url']))
                      options.theme.branding = merchant.organization.invoice_logo_url;
                    @endif
                  @endif
                @endif
                if (merchant) {
                  if (merchant.name) {
                    options.name = merchant.name;
                  }
                  if (merchant.color) {
                    options.theme.color = merchant.color;
                  }
                  if (merchant.image) {
                    options.image = merchant.image;
                  }
                }
                var razorpay = window.razorpay = Razorpay(options);
                if (!data.error && invoiceObj.status !== 'partially_paid') {
                  // razorpay.open();
                }

              }(window.RZP_DATA = window.RZP_DATA || {}));
            </script>
          @endif
        @else
          <script src="{{$data['invoicejs_url']}}"></script>
          <div id="invoice-container"></div>
          <script type="text/javascript">
            (function (globalScope) {

              var data = globalScope.data;

              RazorpayInvoice({
                parentElement: "#invoice-container",
                data: data,
                paymentResponseHandler: function(response) {

                  if (globalScope.hasRedirect()) {

                    return globalScope.redirectToCallback(
                                                           data.invoice.callback_url,
                                                           data.invoice.callback_method,
                                                           response
                                                         );
                  }

                  if (data.invoice.partial_payment) {
                    window.location.reload()
                  } else {
                    let invoice = data.invoice;
                    invoice.amount_due_formatted = '0.00';
                    invoice.amount_paid_formatted = invoice.amount_formatted;
                    invoice.status = 'paid';
                    invoice.is_paid = true;
                    this.rerender(data)
                  }
                  data.invoice.status = 'paid';
                  data.invoice.is_paid = true;
                  this.rerender(data)
                }
              });
            }(window.RZP_DATA = window.RZP_DATA || {}));
          </script>
        @endif
      </div>
    @endif

    <script>
    function showOverlay() {
        document.getElementById('overlay').style.opacity = 1;
    }
    function hideOverlay() {
        document.getElementById('overlay').style.opacity = 0;
    }
    </script>

    <script>

      (function (globalScope) {

        var data = globalScope.data;

        if (globalScope.hasRedirect() &&
            data.request_params.razorpay_payment_id) {

          return globalScope.redirectToCallback(
                                                 data.invoice.callback_url,
                                                 data.invoice.callback_method,
                                                 data.request_params
                                               );
        }
      }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>
  </body>
</html>
