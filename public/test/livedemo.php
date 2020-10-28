<?php

require('vars.php');

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Razorpay - Automatic Checkout</title>
  <link rel="icon" href="data:;base64,=">
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <link href='//fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css'>
  <script src="<?= $checkout ?>/v1/checkout.js" type="text/javascript"></script>
</head>
<body>
  <div class="all-container">
    <div class="header">
      <div class="container">
        <a href="https://www.razorpay.com" target="_blank" class="logo"></a>
        <a class="nav active">Products</a>
        <a class="nav">Delivery</a>
        <a class="nav">Contact Us</a>
        <div class="pull-right">
          <a class="nav">Search</a>
          <a class="nav">Checkout</a>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container">
        <div class="tab">
          <div class="leftcontent">
            <div class="product"></div>
            <div class="thumbnails">
              <div class="thumbnail"></div>
              <div class="thumbnail"></div>
              <div class="thumbnail"></div>
              <div class="thumbnail "></div>
            </div>
          </div>
          <div class="clear"></div>
        </div>
        <div class="tab">
          <div class="rightcontent">
            <h3>Fine Tshirt</h3>
              <div class="rating"><img src="images/rating.png"></div>
              <p class="review">5 Reviews</p>
            <div class="price">₹1</div>

            <p class="description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc fermentum tincidunt libero nec imperdiet. Etiam sed malesuada dolor. Integer id ante ut urna pretium aliquet et et quam. Fusce tempor ultricies velit non finibus. Nullam lacus nulla, vehicula vitae pharetra nec, vestibulum id odio. Nunc quis sapien vestibulum, vulputate ipsum nec, consequat erat. Nunc interdum pharetra commodo. Nullam blandit id neque id ultrices. Proin quis efficitur mauris.</p>
            <p class="wishlist"><img src="images/wishlist.png"></p>
            <div class="pay">
              <input type="button" value="" class="razorpay-payment-button" id="paybtn">
              <script>
              window.r = new Razorpay({
                key: 'rzp_live_ILgsfZCZoFIKMb',
                protocol: 'https',
                hostname: 'api.razorpay.com',
                amount: '100',
                name: 'Merchant Name',
                description: 'Fine tshirt',
                image: 'https://i.imgur.com/3g7nmJC.png',
                prefill: {
                  name: 'QA Razorpay',
                  email: 'qa.testing@razorpay.com',
                  contact: '9999999999'
                },
                handler: function (transaction){
                  alert('You have successfully purchased Fine tshirt\ntransaction id: ' + transaction.razorpay_payment_id);
                }
              })
              document.getElementById('paybtn').onclick = function(){
                r.open()
              }
              </script>
            </div>
          </div>
        </div>
        <div class="clear"></div>
      </div>
    </div>
    <div class="footer">
      <div class="container">
        <a href="https://www.razorpay.com/" target="_blank" class="nav" >Home</a>
        <a href="https://www.razorpay.com/features/" target="_blank" class="nav">Features</a>
        <a href="https://www.razorpay.com/pricing/" target="_blank" class="nav">Pricing</a>
        <a href="https://razorpay.com/docs/" target="_blank" class="nav">Documentation</a>
        <a href="https://www.razorpay.com/contact/" target="_blank" class="nav">Contact Us</a>
      </div>
    </div>
  </div>
</body>
</html>
