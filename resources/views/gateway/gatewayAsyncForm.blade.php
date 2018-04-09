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
      margin: -60px auto 0;
    }

    .spin2 div {
      animation-delay: 0.65s;
    }

    #spinner {
      margin: 20px 0 60px;
    }

    #content {
      max-width: 400px;
      margin: 0 auto;
      padding: 10px;
      box-sizing: border-box;
      position: relative;
    }

    .card {
      background: white;
      border-radius: 2px;
      box-shadow: 0px 4px 20px rgba(0,0,0,0.10);
      padding-bottom: 1px;
    }

    #message-txt b {
      display: block;
      font-size: 20px;
      padding: 0 25px 25px;
    }

    #message-txt {
      line-height: 26px;
      padding: 50px 30px 30px;
      font-size: 16px;
      opacity: 0.8;
    }

    #banner {
      padding: 24px;
    }

    .buttons {
      margin-top: 18px;
      line-height: 56px;
    }

    #retry-btn {
      background: #3395ff;
      color: #fff;
      cursor: pointer;
    }

    #cancel-btn {
      color: #3395ff;
      border-top: 1px solid #ececec;
      cursor: pointer;
    }

    .hide {
      display: none !important;
    }

    form {
      visibility: hidden;
    }

  </style>
</head>
<body>
  <div id='content'>
    <div id="banner">
      <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="28px" style="height: 28px; margin: 20px auto;display: block;">
    </div>

    <div class="card">
      <div id='message-txt'>
        Please accept collect request from Razorpay's VPA in your UPI app
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

    <form method="post"></form>
    <form id="form2" name="form2">
      <input name="type" id="form2_type" value="{{$data['data']['type']}}">
      <input name="gateway" id="form2_gateway" value="{{$data['data']['gateway']}}">
    </form>
    <div id="log"></div>
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
    var $ =  document.getElementById.bind(document);
    var form = $('form');
    var CheckoutBridge = window.CheckoutBridge;
    var isIntentFlow = CheckoutBridge && data.type === 'intent';

    /**
     * Retrieves data from localStorage and updates values.
     */
    function updateFromLocalStorage() {
      // Check for localStorage
      if (typeof localStorage === 'undefined') return;

      // Retrieve from localStorage and parse.
      let stored = localStorage.getItem('pay_data');
      if (!stored) return;
      try {
        stored = JSON.parse(stored);
      } catch (e) {
        return;
      }

      // Set form values.
      $('form2_type').value = stored.form.type;
      $('form2_gateway').value = stored.form.gateway;

      // Set values in variables.
      data = stored.data;
      request_url = stored.request_url;
      key_id = stored.key_id;
      cancel_url = stored.cancel_url;
      callback_url = stored.callback_url;
    }

    /**
     * Stores data in localStorage.
     */
    function storeInLocalStorage() {
      // Check for localStorage.
      if (typeof localStorage === 'undefined') return;

      // Populate values to store.
      let stored = {};
      stored.form = {
        type: $('form2_type').value,
        gateway: $('form2_gateway').value
      };
      stored.data = data;
      stored.request_url = request_url;
      stored.key_id = key_id;
      stored.cancel_url = cancel_url;
      stored.callback_url = callback_url;
      stored.timestamp = Date.now();

      // Store in localStorage.
      localStorage.setItem('pay_data', JSON.stringify(stored));
    }

    // If storage is to be used, update values from storage.
    if (data.storage) {
      updateFromLocalStorage();
    }
    // Otherwise, store in storage.
    else {
      storeInLocalStorage();
    }

    var xhr;
    var lastPollTS;
    var threshold = 1000 * 5; // 15 seconds
    var lastFocus;
    var pollRetriesOnError = 5;
    var pollRetriesSoFar = 0;

    onfocus = function() {
      var now = Date.now();
      $('log').innerHTML += '<br>now ' + now;
      // Focus is being fired for some reason. Don't consider the second one.
      if (lastFocus) {
        if (now - lastFocus <= 1000 * 0.5) {
          lastFocus = now;
          return;
        }
      }

      lastFocus = now;

      $('log').innerHTML += '<br>focus ' + Date.now().toString().slice(-6);
      if (lastPollTS) {
        $('log').innerHTML += '<br>lastPollTS ' + lastPollTS;
        // If last XHR was more than threshold seconds ago, abort XHR and start a new poll.
        $('log').innerHTML += '<br>lastPollTS diff ' + (now - lastPollTS);
        if (xhr && now - lastPollTS >= threshold) {
          $('log').innerHTML += '<br>retrying on focus ' + Date.now().toString().slice(-6);
          xhr.abort();
          fetch(request_url);
        }
      }
    }

    onblur = function() {
      $('log').innerHTML += '<br>blur ' + Date.now().toString().slice(-6);
    }

    // Adds a hash to the URL
    var addHash = function () {
      if (!location.hash) {
        window.location.hash = 'pay';
      }
    }
    onhashchange = addHash;

    let reloadUrl = location.protocol + '//' + location.hostname + '/v1/payments/create/checkout';

    // Method to call when page loads.
    var loadMethod = function () {
      // If localStorage and history exist, only then do this.
      if (typeof history !== 'undefined' && typeof localStorage !== 'undefined') {
        // Push current URL and reload URL to history.
        history.pushState({}, document.title, location.href);
        history.pushState({}, document.title, reloadUrl);
        // Add hash to page.
        addHash();
      }
    }
    loadMethod();

    function track(name, properties) {
      setTimeout(function() {
        properties.CheckoutBridge = !!CheckoutBridge;
        properties.pageData = {
          type: data.type,
          data: data.data,
          key: key_id,
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
        var call = new XMLHttpRequest();
        call.open('post', 'https://lumberjack.razorpay.com/v1/track', true);
        call.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        call.send('key=MC40OTMwNzgyMDM3MDgwNjI3Nw9YnGzW&data=' +
                 encodeURIComponent(btoa(JSON.stringify(payload))));
      })
    }

    {{--
      submit form redirects to callback_url
      or, in case of anrdoid app, call CheckoutBridge.oncomplete
    --}}

    var submitted_count = 0;
    function submitForm(response) {
      {{-- track if page not closed after 4s of calling submitForm --}}
      setTimeout(function() {
        track('no_redirect', {
          count: ++submitted_count
        });
        if (submitted_count && !(submitted_count % 2) && submitted_count < 10) {
          submitForm(response);
        }
      }, 4000);
      if (isIntentFlow) {
        CheckoutBridge.oncomplete(JSON.stringify(response));
      } else {
        if (response && response.type === 'return') {
          var req = response.request;
          var content = req.content;
          form.action = req.url;
          form.method = req.method;
          form.innerHTML = Object.keys(content)
            .map(name => '<input name="' + name + '" value="' + content[name] + '">')
            .join('')
        } else {
          form.action = callback_url;
        }
        // Remove item from storage upon submitting.
        if (typeof localStorage !== 'undefined') {
          localStorage.removeItem('pay_data');
        }
        form.submit();
      }
    }

    function fetch(url, immediate) {
      var totalCalls = 0;

      function fetchAgain(timeout) {
        totalCalls++;
        {{-- 3 minutes --}}
        if (totalCalls > 50 && !(totalCalls % 10)) {
          track('call_count', {
            count: totalCalls,
            url: url
          });
        }

        if (totalCalls > 180) {
          return submitForm();
        }

        setTimeout(function() {
          // If polling, set timestamp.
          if (url === request_url) {
            lastPollTS = Date.now();
          }

          xhr = new XMLHttpRequest();
          xhr.open('get', url, true);

          xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status) {
              var json;
              try {
                json = JSON.parse(xhr.responseText);
                if (!json || typeof json !== 'object') {
                  throw 'non object:' + json;
                }
              } catch(e) {
                json = {
                  message: e.message,
                  error: {
                    description: 'Parsing error'
                  },
                  xhr: {
                    status: xhr.status,
                    text: xhr.responseText,
                    url: url
                  }
                };
              }
              if (json.status === 'created') {
                return fetchAgain();
              } else {
                try {
                  if (
                    json.razorpay_payment_id ||
                    (json.error && json.error.description !== 'The payment has already been processed') ||
                    json.version === 1
                  ) {
                    return submitForm(json);
                  }
                } catch(e) {
                  track('ajax_onerror', {
                    status: xhr.status,
                    url: url
                  });
                  if (pollRetriesSoFar < pollRetriesOnError) {
                    pollRetriesSoFar++;
                    fetchAgain(timeout || 4000);
                  }
                }
              }

              track('unexpected', {
                json: json,
                status: xhr.status,
                text: xhr.responseText,
                url: url
              })
              setTimeout(submitForm, 4000);
            }
          }
          xhr.onerror = function() {
            track('ajax_onerror', {
              status: xhr.status,
              url: url
            })
            fetchAgain(1000);
          }
          xhr.send(null);
        }, timeout || 4000);
      }

      fetchAgain(immediate);
    }

    if (isIntentFlow) {
      var intent_url = data.data.intent_url;

      function initUpiActivity() {
        try {
          CheckoutBridge.callNativeIntent(intent_url);
          $('spinner').className = 'hide';
          $('retry-btn').className = 'hide';
          $('message-txt').innerHTML = '<b>Select UPI App</b>Payment will be made to Razorpay\'s VPA';
          window.pollStatus = function(resp) {
            if (!Object.keys(resp).length || /txnid=(undefined|null)/i.test(resp.response)) {
              $('cancel-btn').className = '';
              $('retry-btn').className = '';
              $('spinner').className = 'hide';
            } else {
              fetchWait(request_url);
            }
          }
        } catch(e) {
          track('android_error', {
            error: e.message
          })
          setTimeout(submitForm, 3000);
        }
      }
      initUpiActivity();
    } else {
      fetch(request_url);
    }

    $('cancel-btn').onclick = function() {
      fetchWait(cancel_url);
    }

    function fetchWait(url) {
      $('spinner').className = '';
      $('cancel-btn').className = 'hide';
      $('retry-btn').className = 'hide';
      $('message-txt').innerHTML = "Please wait...";
      fetch(url, 1);
    }

  </script>

</body>
</html>
