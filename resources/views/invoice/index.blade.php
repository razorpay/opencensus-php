<!doctype html>
<html>
  <head>
    <title>Invoice</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
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
      body {
        font-family: -apple-system, ubuntu, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
        color: #414141;
        background: #ecf0f1;
      }

      @media print {
        body {
          background-color: #fff;
        }
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
                  theme: {
                    close_button: false
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
                  razorpay.open();
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
