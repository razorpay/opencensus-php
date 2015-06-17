<?php

require('vars.php');

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Razorpay - Automatic Checkout</title>
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <link href='http://fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css'>
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
            <div class="price"><img src="images/price.png"></div>
            <p class="description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc fermentum tincidunt libero nec imperdiet. Etiam sed malesuada dolor. Integer id ante ut urna pretium aliquet et et quam. Fusce tempor ultricies velit non finibus. Nullam lacus nulla, vehicula vitae pharetra nec, vestibulum id odio. Nunc quis sapien vestibulum, vulputate ipsum nec, consequat erat. Nunc interdum pharetra commodo. Nullam blandit id neque id ultrices. Proin quis efficitur mauris.</p>
            <p class="wishlist"><img src="images/wishlist.png"></p>
            <form class="pay" action="/purchase" method="POST">
              <!-- <input placeholder="Email*" required type="email">
              <input placeholder="Shipping Address" pattern=".{3,}"> -->
              <script
                src="https://checkout.razorpay.com/v1/checkout.js"
                data-key="rzp_live_ILgsfZCZoFIKMb"
                data-amount="54900"
                data-name="Merchant Name"
                data-description="Purchase Description"
                data-image="https://i.imgur.com/3g7nmJC.png"
                data-prefill.name="Harshil Mathur"
                data-prefill.email="harshil@razorpay.com"
              ></script>
            </form>
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
        <a href="https://docs.razorpay.com/" target="_blank" class="nav">Documentation</a>
        <a href="https://www.razorpay.com/contact/" target="_blank" class="nav">Contact Us</a>
      </div>
    </div>
  </div>
</body>
</html>
