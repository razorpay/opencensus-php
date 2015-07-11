<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body style="text-align: center; color: #555; font-family: sans-serif">
    <h1>Demo Redirect Payment Page</h1>
    <button id="button">Pay ₹225</button>
    <script>
        var r = new Razorpay({
          key: 'rzp_test_1DP5mmOlF5G5ag',
          callback_url: 'http://localhost:9000',
          amount: '22500',
          name: 'Hike',
          description: '', // purchase description to show below 'name'
          image: '', // can be base64, or relative/absolute url
          prefill: {
            email: 'some@one.com',
            name: 'Some One',
            contact: '8877799990'
          },
          modal: {
            ondismiss: function(){
              // on pressing close button in payment form
              alert('Customer has cancelled the payment. \nAdd your cancel callback here.');
            }
          }
        })
        document.getElementById('button').onclick = function(){
          r.open();
        }

        // or you can new Razorpay(options).open() to show form instantaneously
    </script>
</body>