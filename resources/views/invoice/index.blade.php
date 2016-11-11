<!doctype html>
<html>
  <head>
    <title>Invoice </title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    @if ($data['environment'] !== 'production')
    <script>
      var Razorpay = {
        config: {
          api: '/'
        }
      }
    </script>
    @endif
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
    body {
      text-align: center;
      font-family: -apple-system, ubuntu, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
      color: #414141;
      background: #ecf0f1;
    }
    path {
      fill: #6DCA00;
    }
    h3 {
      font-weight: normal;
    }
    #success {
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
    span {
      float: right;
    }
    </style>
  </head>
<body>
  <div id='success'>
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.959 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591z"/></svg>
    <h3>Your Payment has been received</h3>
    <div id='break'>
      <div>Amount Paid<span>₹ {{ $data['amount']/100 }}</span></div>
      <div>Invoice ID<span>{{ $data['invoice_id'] }}</span></div>
      <div>Payment ID<span id='pay_id'></span></div>
    </div>
  </div>
</body>
  <script>
    var data = {!!utf8_json_encode($data)!!};
    var options = {
      key: data.key_id,
      amount: data.amount,
      description: 'Invoice #' + data.invoice_id,
      handler: function(response) {
        document.querySelector('#pay_id').innerHTML = response.razorpay_payment_id;
        document.querySelector('#success').style.display = 'block';
      },
      prefill: {
        contact: data.customer_contact,
        email: data.customer_email
      }
    }
    Razorpay.open(options);
  </script>
