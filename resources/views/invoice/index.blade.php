<!doctype html>
<html>
  <head>
    <title>Invoice</title>
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
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="http://127.0.0.1:8080/dist/invoice.js"></script>
  </head>
  <body>
    <div id="invoice-container"></div>
    <script type="text/javascript">
      var data = {!!utf8_json_encode($data)!!};
      data.merchant.image = 'http://i.imgur.com/THWZJUM.png'
      console.log(data)

      RazorpayInvoice({
        parentElement: "#invoice-container",
        data: data,
        paymentResponseHandler: function(response) {
          if (data.merchant && data.merchant.name) {
            document.querySelector('#success h3').innerHTML = 'Thank you for your payment on ' + data.merchant.name;
          }
          document.querySelector('#pay_id').innerHTML = response.razorpay_payment_id;
          document.body.className = 'paid';
        }
      })
    </script>

  </body>
</html>
