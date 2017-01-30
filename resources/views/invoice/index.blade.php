<!doctype html>
<html>
  <head>
    <title>Invoice </title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet" type="text/css"></link>
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    @if ($data['environment'] !== 'production')
    <script>
      var Razorpay = {
        config: {
          api: '/'
        }
      }
    </script>
    @endif

    <?php
      $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';
    ?>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="http://127.0.0.1:8080/dist/invoice.js"></script>
    <style>
      #failure path {
        fill: #e74c3c;
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
      #break {
        color: #777;
        font-size: 14px;
        margin: 30px -30px 0;
        padding: 30px 30px 0;
        border-top: 1px dashed #e3e4e6;
        text-align: left;
        line-height: 24px;
      }
      #success {
        display: none;
      }
      .paid #success {
        display: block;
      }
    </style>
  </head>
  <body class="{{$data['status']}}">
    <div id="invoice-container"></div>
    <script type="text/javascript">
      var data = {!!utf8_json_encode($data)!!};
      data.merchant_details.image = 'http://i.imgur.com/THWZJUM.png'
      console.log(data)

      RazorpayInvoice({
        parentElement: "#invoice-container",
        data: data,
        openCheckoutOnInit: !data.error,
        paymentResponseHandler: function(response) {
          if (data.merchant_details && data.merchant_details.name) {
            document.querySelector('#success h3').innerHTML = 'Thank you for your payment on ' + data.merchant_details.name;
          }
          document.querySelector('#pay_id').innerHTML = response.razorpay_payment_id;
          document.body.className = 'paid';
        }
      })
    </script>


<!--     <div id="success" class="card">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.959 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591z"/></svg>
      <h3>Your Payment has been received</h3>
      <div id='break'>
        <div>Amount Paid<span>₹ {{ $data['amount']/100 }}</span></div>
        <div>Invoice ID<span>{{ $data['invoice_id'] }}</span></div>
        <div>Payment ID<span id='pay_id'>{{ $data['payment_id'] or '' }}</span></div>
      </div>
    </div>
 -->
    @if ($data['view_less'] === true)
      @if ($data['status'] !== 'paid')
        @if (isset($data['error']))
          <div id="failure" class="card">
            {!! $error_icon !!}
            <h2>Payment Failed</h2>
            <p>{{ $data['error']['description'] }}</p>
            <button onclick="razorpay.open()">Retry</button>
          </div>
        @endif
      @endif
    @else
      <div id="failure" class="card">
        {!! $error_icon !!}
        <h2>Error</h2>
        <p>This invoice cannot be displayed. Please contact the merchant for assistance.</p>
      </div>
    @endif
  </body>
</html>
