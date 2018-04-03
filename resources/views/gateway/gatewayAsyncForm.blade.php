<!DOCTYPE html>
<html>
<head>
  <title>Payment in progress • Razorpay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    @-webkit-keyframes spin {
      0%{-webkit-transform:scale(0.5);opacity:0;border-width:8px}
      20%{-webkit-transform:scale(0.6);opacity:0.8;border-width:4px}
      90%{-webkit-transform: scale(1);opacity:0}
    }
    @-moz-keyframes spin {
      0%{-moz-transform:scale(0.5);opacity:0;border-width:8px}
      20%{-moz-transform:scale(0.6);opacity:0.8;border-width:4px}
      90%{-moz-transform:scale(1);opacity:0}
    }
    @keyframes spin {
      0% {transform:scale(0.5);opacity:0;border-width:8px}
      20% {transform:scale(0.6);opacity:0.8;border-width:4px}
      90% {transform:scale(1);opacity:0}
    }

    html,body {
      font-family:'lato', -apple-system, BlinkMacSystemFont,  "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell",  "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
      background: #FBFBFB;
      text-align: center;
    }

    .card {
      padding: 24px;
      background: #fff;
      margin: 24px 0;
    }

    .spin {
      width: 60px;
      height: 60px;
      margin: 0 auto;
    }

    .spin div {
      width: 100%;
      height: 100%;
      vertical-align: middle;
      display: inline-block;
      border-radius: 50%;
      border: 4px solid #3395ff;
      -webkit-animation: spin 1.3s linear infinite;
         -moz-animation: spin 1.3s linear infinite;
        -ms-animation: spin 1.3s linear infinite;
         -o-animation: spin 1.3s linear infinite;
          animation: spin 1.3s linear infinite;
      box-sizing: border-box;
      opacity: 0;
    }

    .spin2 {
      margin: -60px auto 20px;
    }

    .spin2 div {
      animation-delay: 0.65s;
    }

    #spinner {
      padding: 15px 0 10px;
    }

    #content {
      max-width: 480px;
      margin: 0 auto;
      padding: 24px;
      box-sizing: border-box;
      position: relative;
    }

    .loadingcard {
      background: white;
      padding: 40px 0 0;
      border-radius: 2px;
      box-shadow: 0px 4px 20px rgba(0,0,0,0.10);
    }

    #message-txt b {
      display: block;
      font-size: 20px;
      padding: 0 25px 25px;
    }

    #message-txt {
      font-size: 16px;
      margin-top: 12px;
      opacity: 0.8;
    }

    #banner {
      padding: 24px;
    }

    .buttons div {
      padding: 15px;
    }

    #retry-btn {
      display: block;
      background: #3395ff;
      color: #fff;
      border: 0;
      border-bottom-left-radius: 2px;
      border-bottom-right-radius: 2px;
      cursor: pointer;
    }

    #cancel-btn {
      color: #3395ff;
      margin-top: 40px;
      border-top: 1px solid #ececec;
      cursor: pointer;
    }

    .hide {
      display: none !important;
    }

  </style>
</head>
<body>
  <div id='content'>
    <div id="banner">
      <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="28px" style="height: 28px; margin: 20px auto;display: block;">
    </div>

    <div class="loadingcard">
      <div id='message-txt'>
        @if ($data['data']['type'] === 'intent')
          <b>Select UPI App</b>
          Payment will be made to Razorpay's VPA
        @else
          Please accept collect request from Razorpay's VPA in your UPI app
        @endif
      </div>

      <div id="spinner">
        <div class="spin"><div></div></div>
        <div class="spin spin2"><div></div></div>
      </div>

      <div class="buttons">
        <div id="cancel-btn"><b>Cancel Payment</b></div>
        <div class="hide" id="retry-btn" onclick="initUpiActivity()"><b>Retry Payment</b></div>
      </div>
    </div>

    <form id='form' method="POST">
    </form>
    <form id="form2" name="form2">
      <input type="hidden" name="type" value="{{$data['data']['type']}}">
      <input type="hidden" name="gateway" value="{{$data['data']['gateway']}}">
    </form>
  </div>

  <script type="text/javascript">
    // Async Payment data //
    var data = {!!utf8_json_encode($data['data'])!!};
    // Async Payment data //

    var request_url = data.request.url;
    var key_id = '{{ App::getFacadeRoot()['basicauth']->getPublicKey() }}';
    var payment_base = '{{$data["api"]}}/v1/payments/' + data.payment_id;
    var cancel_url = payment_base + '/cancel?key_id='+key_id;
    var callback_url = payment_base + '/redirect_callback?key_id='+key_id;
    var gel =  document.getElementById.bind(document);
    var CheckoutBridge = window.CheckoutBridge;

    function track(name, properties) {
      properties.CheckoutBridge = !!CheckoutBridge;
      properties.pageData = {
        type: data.type,
        data: data.data,
        payment_id: data.payment_id
      }
      var payload = {
        context: {
          user_agent: null
        },
        events: [{
          event: name,
          properties: properties,
          timestamp: Date.now()
        }]
      };
      
      if (key_id.slice(0, 5) === 'rzp_t') return console.log(payload);   
      var xhr = new XMLHttpRequest();
      xhr.open('post', 'https://lumberjack.razorpay.com/v1/track', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send('key=MC40OTMwNzgyMDM3MDgwNjI3Nw9YnGzW&data=' +
               encodeURIComponent(btoa(JSON.stringify(payload))));
    }

    function submitForm(response) {
      if (CheckoutBridge) {
        CheckoutBridge.oncomplete(JSON.stringify(response));
      } else {
        gel('form').setAttribute('action', callback_url);
        gel('form').submit();        
      }
    }

    function fetch(url, timeout) {
      var totalCalls = 0;
      function fetchAgain() {
        totalCalls++;
        // 5 minutes
        if (totalCalls > 70 && !(totalCalls % 10)) {
          track('call_count', {
            count: totalCalls,
            url: url
          });
        }

        if (totalCalls > 175) {
          return submitForm();
        }

        setTimeout(function() {

          var xhr = new XMLHttpRequest();
          xhr.open('get', url, true);

          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status) {
              var json;
              try {
                json = JSON.parse(xhr.responseText);
              } catch(e) {
                json = {
                  error: {
                    description: 'Parsing error'
                  }
                };
                track('ajax_e', {
                  status: xhr.status,
                  text: xhr.responseText,
                  url: url
                });
              }
              if (json.status === 'created') {
                fetchAgain(url);
              } else if (json.razorpay_payment_id || json.error) {
                /*
                 * Redirecting to callback_url regardless of whether payment is
                 * succesful or not
                 */
                submitForm();
              } else {
                track('unexpected', {
                  json: json,
                  status: xhr.status,
                  text: xhr.responseText,
                  url: url
                })
                setTimeout(submitForm, 4000);
              }
            }
          }
          xhr.onerror = function() {
            track('ajax_onerror', {
              status: xhr.status,
              url: url
            })
            fetchAgain();
          }
          xhr.send(null);
        }, timeout || 4000);
      }
      fetchAgain();
    }

    if (CheckoutBridge && data.type === 'intent') {
      var intent_url = data.data.intent_url;

      function initUpiActivity() {
        gel('retry-btn').className = 'hide';
        CheckoutBridge.callNativeIntent(intent_url);        
      }
      initUpiActivity();

      window.pollStatus = function(resp) {
        if (!Object.keys(resp).length || /txnid=(undefined|null)/i.test(resp.response)) {
          gel('cancel-btn').className = '';
          gel('retry-btn').className = '';
          gel('spinner').className = 'hide';
        } else {
          gel('cancel-btn').className = 'hide';
          gel('retry-btn').className = 'hide';
          gel('message-txt').innerHTML = "Please wait...";
          fetch(poll_url, 1);
        }
      }
    } else {
      fetch(request_url)
    }

    gel('cancel-btn').onclick = function() {
      fetch(cancel_url);
    }

  </script>

</body>
</html>
