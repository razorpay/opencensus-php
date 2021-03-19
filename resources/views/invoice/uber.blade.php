<!doctype html>
<html>
  <head>
    <title>Uber Payment</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link rel="shortcut icon"type="image/x-icon" href="data:image/x-icon;,">
    <?php
      $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path style="fill: #e74c3c" d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';
    ?>

    @php
      $should_pay = isset($data['invoice']) && $data['invoice']['status'] !== 'paid';
      $theme_color = isset($data['merchant']) ? $data['merchant']['brand_color'] : '#119399';
    @endphp
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
    @if ($should_pay)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif
    <style>
      body {
        font-family: -apple-system, ubuntu, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
        color: #414141;
        background: #f4f7fa;
        padding: 15px;
        font-size: 14px;
        line-height: 22px;
        margin: 0;
      }
      @if ($should_pay)
      body:before {
        content: '';
        position: absolute;
        width: 100%;
        height: 60px;
        top: 0;
        left: 0;
        background: {{$theme_color}};
      }
      @endif
      body.paid:before {
        content: none;
      }
      h3 {
        font-weight: normal;
      }

      .container {
        margin: 15px auto;
        max-width: 500px;
        position: relative;
      }
      #box {
        padding: 15px;
        border-radius: 1px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        color: #777;
      }

      #logo {
        float: right;
        width: 50px;
      }

      #name {
        font-size: 16px;
        color: #333;
        margin-bottom: 2px;
      }

      label {
        font-weight: bold;
        color: #999;
        text-transform: uppercase;
        font-size: 12px;
        margin: 30px 0 6px;
        display: block;
      }

      input {
        width: 100%;
        display: block;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 0;
        border-top: 1px solid #eee;
        margin-top: -1px;
        padding: 12px 16px;
        -webkit-box-sizing: border-box;
        outline: none;
      }

      .tile {
        float: left;
        width: 25%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        height: 110px;
        text-align: center;
        cursor: pointer;
        color: {{$theme_color}};
      }
      .tile svg {
        width: 52px;
        display: block;
        margin: 14px auto 2px;
      }
      .tile path {
        fill: {{$theme_color}};
      }

      .help {
        opacity: 0;
        transform: translateY(-10px);
        color: #fff;
        font-weight: bold;
        position: absolute;
        line-height: 16px;
        padding: 8px 12px;
        font-size: 12px;
        background: #555;
        box-shadow: rgba(0,0,0,0.05) 1px 1px 2px 0;
        z-index: 3;
        border-radius: 3px;
        margin: 0 0 0 15px;
        pointer-events: none !important;
      }
      .help:after {
        content: "";
        position: absolute;
        width: 0;
        height: 0;
        border-width: 5px;
        border-style: solid;
        border-color: transparent transparent #555;
        bottom: 100%;
        left: 20px;
        margin: 0 0 -1px -10px;
      }
      .invalid+.help {
        display: block;
        opacity: 1;
        transform: none;
        transition: .2s cubic-bezier(.6,1.6,.8,1) transform,.2s ease-out opacity;
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
      #success:not(.vis) {
        display: none;
      }
      #partial-content {
        text-align: center;
        display: none;
      }
      #partial-container.partially_paid > * {
        display: none;
      }
      #partial-container.partially_paid #partial-content {
        display: block;
      }

      button {
        background-color: #4994E6;
        color: #fff;
        border: 0;
        outline: none;
        cursor: pointer;
        font: inherit;
        padding: 10px 20px;
        border-radius: 2px;
      }

      button:active {
        box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset, 0 0 6px rgba(0,0,0,.2) inset;
      }

    </style>
  </head>
  <body>
    @if (isset($data['error']))
      <div id="failure" class="card">
        {!! $error_icon !!}
        <h2>Error</h2>
        <p>{{$data['error']['description']}}</p>
      </div>
    @elseif ($should_pay)
      <div class="container">
        <div id="box">
          <div class={{$data['invoice']['status']}}>
            <img id="logo" src="{{$data['merchant']['image']}}">
            <div id="name">{{{ $data['invoice']['customer_details']['customer_name'] }}}&nbsp;</div>
            Total Amount: <strong class="due">₹{{$data['invoice']['amount_formatted']}}</strong><br>
            Total Balance Due: <strong class="due">₹{{$data['invoice']['amount_due_formatted']}}</strong>
            <hr>
            <div>{!! nl2br(e($data['invoice']['description'], false)) !!}</div>
          </div>
        </div>
        <div id="partial-container" class="{{$data['invoice']['status']}}">
          <div id="partial-content">
            <p><strong>Thanks for your payment of ₹{{$data['invoice']['amount_paid_formatted']}}.</strong></p>
            <button id="partial-button">Pay Rest</button>
          </div>
          <label>Enter Phone Number</label>
          <input
            id="contact"
            type="number"
            maxlength="10"
            placeholder="Enter 10 Digit Indian Phone Number"
            pattern="^\d{10}$"
            value="{{{ $data['invoice']['customer_details']['customer_contact'] }}}"
          >
          <span id="contact-help" class="help">Please enter 10 digit indian phone number</span>
          @if ($data['invoice']['partial_payment'])
            <label>Enter Amount to Pay</label>
            <input
              id="amount"
              type="number"
              placeholder="Enter Amount"
              pattern="^\d+$"
              value="{{$data['invoice']['amount_due']/100}}">
          @else
            <input id="amount" type="hidden" value="{{$data['invoice']['amount_due']/100}}">
          @endif
          <span id="amount-help" class="help">Please enter amount to pay, upto ₹{{$data['invoice']['amount_due_formatted']}}</span>

          <label>Select Payment Method</label>
          <div class="tile" method="card"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 -390 2000 2000"><g transform="matrix(1 0 0 -1 0 1610)"><path fill="currentColor" d="M90 1502v-1081q0 -15 7.5 -27.5t20 -19.5t26.5 -7h1730q15 0 27.5 7t19.5 19.5t7 27.5v1081q0 14 -7 26.5t-19.5 20t-27.5 7.5h-1730q-22 0 -38 -16t-16 -38zM36 1502q0 45 31.5 76.5t76.5 31.5h1730q45 0 76.5 -31.5t31.5 -76.5v-1081q0 -45 -31.5 -76.5t-76.5 -31.5h-1730q-44 0 -76 31.5t-32 76.5v1081zM90 1340h1838v-271h-1838v271zM198 799h703v-108h-703v108zM198 637h433v-108h-433v108z" /></g></svg>
          Card</div>
          <div class="tile" method="netbanking"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 -390 2000 2000"><g transform="matrix(1 0 0 -1 0 1610)"><path fill="currentColor" d="M0 235h1985v-63h-1985v63zM117 360h1751v-62h-1751v62zM375 985h125v-500h-125v500zM750 985h125v-500h-125v500zM1125 985h125v-500h-125v500zM1500 985h125v-500h-125v500zM117 1172h1751v-62h-1751v62zM117 1317l876 293l875 -293v-59h-1751v59z" /></g></svg>
          Netbanking</div>
          <div class="tile" method="wallet"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 -390 2060 2000"><g transform="matrix(1 0 0 -1 0 1610)"><path fill="currentColor" d="M116 1479v-1111q0 -15 7 -28t20 -20.5t28 -7.5h1778q23 0 39 16.5t16 39.5v1111q0 23 -16 39t-39 16h-1778q-23 0 -39 -16t-16 -39zM60 1479q0 46 32.5 78.5t78.5 32.5h1778q46 0 78.5 -32.5t32.5 -78.5v-1111q0 -46 -32.5 -78.5t-78.5 -32.5h-1778q-46 0 -78.5 32.5t-32.5 78.5v1111zM1671 757q0 -23 16 -39.5t40 -16.5h277v-55v201v187v-55h-277q-24 0 -40 -16.5t-16 -39.5v-166zM1616 923q0 46 28 78.5t69 32.5h291v-187v-201h-291q-41 0 -69 32t-28 79v166zM393 1298q0 29 20.5 49.5t49.5 20.5h1541v-139h-1541q-19 0 -35 9t-25.5 25t-9.5 35z" /></g></svg>
          Wallet</div>
          <div class="tile" method="upi"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 -390 2000 2000"><g transform="matrix(1 0 0 -1 0 1610)"><path fill="currentColor" d="M56 388q0 -15 7 -28t20 -20.5t28 -7.5h1778q23 0 39 16.5t16 39.5v1111q0 23 -16 39t-39 16h-1778q-23 0 -39 -16t-16 -39v-1111zM0 1499q0 46 32.5 78.5t78.5 32.5h1778q46 0 78.5 -32.5t32.5 -78.5v-1111q0 -46 -32.5 -78.5t-78.5 -32.5h-1778q-46 0 -78.5 32.5t-32.5 78.5v1111zM1722 943l-222 -222l29 107l115 115l-53 116l29 107zM1558 1059l53 -116l-115 -115l-107 -107l120 445zM484 781q22 0 39 7.5t28.5 20.5t18 31.5t6.5 41.5v241h75v-241q0 -36 -11.5 -66t-33.5 -52.5t-52.5 -34.5t-69.5 -12t-69.5 12t-52 34.5t-33 52.5t-11.5 66v241h74v-241q0 -23 6.5 -41.5t18 -31.5t28.5 -20.5t39 -7.5zM866 921q20 0 35.5 5t25.5 14.5t15 23.5t5 31q0 16 -5 29t-15 22t-25.5 14t-35.5 5h-56v-144h56zM866 1123q40 0 69.5 -9.5t48.5 -26.5t28.5 -40.5t9.5 -51.5q0 -29 -10 -53.5t-29.5 -42t-48.5 -27.5t-68 -10h-56v-141h-75v402h131zM1154 721h-75v402h75v-402z" /></g></svg>
          UPI</div>
        </div>
      </div>
      <script>
        var gel = function(id){return document.getElementById(id)}
        var amount = gel('amount');
        var contact = gel('contact');
        var contactHelp = gel('contact-help');
        var amountHelp = gel('amount-help');
        var partialButton = gel('partial-button');

        if (partialButton) {
          partialButton.onclick = function() {
            gel('partial-container').className = 'issued';
          }
        }

        function oninput(e) {
          e.target.className = new RegExp(e.target.getAttribute('pattern')).test(e.target.value) ? '' : 'invalid';
        }

        onhashchange = function() {
          !location.hash && r && r.close();
        }
        var r;
        Array.prototype.forEach.call(document.querySelectorAll('.tile'), function(tile) {
          tile.onclick = function(e) {
            amount.oninput = oninput;
            contact.oninput = oninput;
            var amountVal = 100*amount.value;
            var contactVal = contact.value;

            if (contactVal.length !== 10) {
              contact.focus();
              contact.className = 'invalid';
              return;
            }

            if (!amountVal || amountVal > {{$data['invoice']['amount_due']}}) {
              amount.focus();
              amount.className = 'invalid';
              return;
            }

            location.hash = '#checkout';
            r = Razorpay.open({
              key: "{{$data['key_id']}}",
              theme: {
                close_method_back: true,
                close_button: false
              },
              handler: function() {
                location.hash = '';
                location.reload();
                document.querySelector('.container').style.display = 'none';
                document.querySelector('#success').style.display = 'block';
                document.querySelector('#break').innerHTML = 'Please wait...';
                document.body.className = 'paid';
              },
              description: "{!! preg_replace('/\n/m', '\n', e($data['invoice']['description'], false)) !!}",
              invoice_id: "{{$data['invoice']['id']}}",
              callback_url: location.href,
              remember_customer: false,
              prefill: {
                method: e.currentTarget.getAttribute('method'),
                amount: amountVal,
                contact: contactVal,
                @if ($data['invoice']['customer_details']['customer_email'])
                email: "{{{ $data['invoice']['customer_details']['customer_email'] }}}"
                @else
                email: 'void@razorpay.com'
                @endif
              }
            })
          }
        })
      </script>
    @elseif ($data['invoice'])
      <div id="success" class="card @if($data['invoice']['status'] === 'paid') vis @endif">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path style="fill: #6DCA00" d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.959 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591z"/></svg>
        <h3>Your Payment has been received</h3>
        <div id='break'>
          <div>Amount Paid: <span>₹ {{ $data['invoice']['amount']/100 }}</span></div>
          <div>Invoice ID: <span>{{ $data['invoice']['id'] }}</span></div>
        </div>
      </div>
    @endif
  </body>
</html>
