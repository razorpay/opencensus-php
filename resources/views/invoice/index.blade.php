<!doctype html>
<html>
  <head>
    <title>Invoice ·</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  </head>
<body>
  <div id='success' style='display: none'>
    <h1>Your Payment has been received</h1>
  </div>
</body>
  <script>
    var data = {!!utf8_json_encode($data)!!};
    var options = {
      key: data.key_id,
      amount: data.amount,
      description: 'Invoice #' + data.invoice_id,
      handler: function(response) {
        document.querySelector('success').style.display = 'block';
      },
      prefill: {
        contact: data.customer_contact,
        email: data.customer_email
      }
    }
    Razorpay.open(options);
  </script>
