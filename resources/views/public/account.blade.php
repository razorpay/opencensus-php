<!DOCTYPE html>
<html dir='ltr'>
  <head>
    <meta charset='utf-8'>
    <title>Razorpay · Manage Your Account</title>
    <meta name='viewport' content='user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1'>
    <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css?family=PT+Mono' rel='stylesheet'>
    <link rel='stylesheet' href='{{{$static}}}/style.css'>
  </head>
  <body id='body'>
    <ul id="nav">
      <div class="container"><i class="ham-menu">A</i>
          <li>
              <a id="logo-link" href="https://razorpay.com/" target="_parent"></a>
          </li>
          <div id="ham">
              <li><a href="https://razorpay.com/features/" target="_parent">Features</a></li>
              <li><a href="https://razorpay.com/pricing/" target="_parent">Pricing</a></li>

              <div class="float-right">
                  <li><a href="https://razorpay.com/about" target="_parent">About us</a></li>
                  <li><a href="https://razorpay.com/contact" target="_parent">Contact us</a></li>
              </div>
          </div>
      </div>
    </ul>
    <div id="main-content">
      <div class="loginOverlay mfix center">
        <div class="login mchild section loading">
          <div class="head mfix">
            <div class="title mchild">
              LOGIN
            </div>
          </div>
          <form id="login">
            <div class="center prompt">Log in with your phone number to access your saved cards</div>
            <div class="elem elem-contact">
              <div id="code" class="select" tabindex="1">
              </div>
              <input id="contact" type="tel" name="contact" placeholder="Phone Number" required pattern="^[0-9]{8,15}$" maxlength="15"/>
            </div>
            <div class="elem elem-email">
              <input id="email" type="email" name="email" placeholder="Email Address" required pattern="^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$"/>
            </div>
            <div class="submit inputwrapper center">
              <button class="rounded">SUBMIT</button>
            </div>
          </form>
          <div class="loadingscreen mfix loader">
              <div class="mchild">
                <div class="prompt">Looking for saved user</div>
                <div class="spin"><div></div></div>
                <div class="spin spin2"><div></div></div>
                <button class="dismiss">RETRY</button>
              </div>
          </div>
        </div>

        <div class="otpform mchild section">
          <div class="head mfix">
            <div class="title mchild">
              LOG IN
            </div>
          </div>
          <form id="otpform">
            <div class="phone"></div>
            <div class="center prompt">Enter the One Time Password to authenticate your device</div>
            <div class="elem elem-otp">
              <input id="otp" type="tel" name="otp" placeholder="One Time Password" required pattern="^[0-9]{6,6}$" maxlength="6" autocomplete="off" autofocus="" />
            </div>
            <div class="center">
              <div id='resendotp' class="link">Resend OTP</div>
            </div>
            <div class="submit inputwrapper center">
              <button class="rounded">SUBMIT</button>
            </div>
          </form>
          <div class="loadingscreen mfix">
              <div class="mchild">
                <div class="prompt">Looking for saved user</div>
                <div class="spin"><div></div></div>
                <div class="spin spin2"><div></div></div>
                <button class="dismiss">RETRY</button>
              </div>
          </div>
        </div>
      </div>
      <div id='topbar'>
        <div class="container">
          <div class="heading">Manage your Cards</div>
          <div class="profile mfix">
            <div class="mchild">
              <div class="contact">9876543210</div>
              <div class="logout">Logout</div>
            </div>
          </div>
        </div>
      </div>

      <div id="cards" class="container section">
        <div class="head mfix">
          <div class="title mchild float-left">
            CARDS
          </div>
          <div class="action mchild">
            ADD A NEW CARD
          </div>
        </div>
        <div class="cards">
        </div>
      </div>
      <div id='transactions' class="container section">
        <div class="head mfix">
          <div class="title mchild float-left">
            PAYMENTS
          </div>
          <div class="action mchild">
            August, 2016
          </div>
        </div>
        <div id="transactions">
        </div>
      </div>
      <div id='checkoutform'>
        <div class="checkoutOverlay">
          <div id="modal"></div>
        </div>
      </div>
    </div>
    <script>
    Razorpay = {
      config: {
        api: '{{$api}}',
        version: 'v1/'
      }
    }
    var options = {
        // "key": "rzp_live_gC8obGlwaRlyui",
        // "key": "rzp_test_1DP5mmOlF5G5ag",
        "key": "rzp_live_ILgsfZCZoFIKMb",
        "amount": "100", // 2000 paise = INR 20
        "name": "Razorpay",
        "description": "Demo payment to save card",
        "image": "https://razorpay.com/images/brand/glyph-rounded-square.svg",
        "handler": function (response){
            window.location.reload();
        },
        "remember_customer": true,
        "theme": {
            "color": "#F37254"
        }
    };
    </script>
    <script src="{{$checkout}}/v1/checkout.js"></script>
    <script src='{{$static}}/script.js'></script>
  </body>
</html>
