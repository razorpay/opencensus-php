<!DOCTYPE html>
<html>
<head>
  <title>Payment in progress • Razorpay</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
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

    .banner {
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
    <div class="banner">
      {{-- Add the merchant logo if it exists --}}
      @if (isset($data['data']['merchant_logo_url']) === true)
        <img src="{{$data['data']['merchant_logo_url']}}" id="merc_logo" style="max-height: 52px; margin: 20px auto; display: block;">
      @endif
    </div>

    <div class="card">
      <div id='message-txt'>
        Please accept the collect request sent to your UPI app
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

    @if (!$data['data']['nobranding'])
    <div class="banner">
      <img src="https://cdn.razorpay.com/logo.png" id="logo" height="28px" style="height: 28px; margin: 20px auto;display: block;">
    </div>
    @endif

    <form id="form" method="post"></form>
    <form id="form2" name="form2">
      <input name="type" id="form2_type" value="{{$data['data']['type']}}">
      <input name="gateway" id="form2_gateway" value="{{$data['data']['gateway']}}">
    </form>
  </div>

  <script type="text/javascript">
    // Async Payment data //
    var data = {!!utf8_json_encode($data['data'])!!};
    // Async Payment data //

    try { CheckoutBridge.setPaymentID(data.payment_id) } catch(e){}

    var request_url = data.request.url;
    var key_id = '{{ App::getFacadeRoot()['basicauth']->getPublicKey() }}';
    var payment_base = '{{$data["api"]}}/v1/payments/' + data.payment_id;
    var query_param = '';
    if (key_id != '') {
        query_param = '?key_id=' + key_id;
    }
    var cancel_url = payment_base + '/cancel'+query_param;
    var callback_url = payment_base + '/redirect_callback'+query_param;

    var $ =  function (id) {
      return document.getElementById(id);
    }

    if (!Date.now) {
      Date.now = function () { return +new Date(); };
    }

    var form = $('form');
    var CheckoutBridge = window.CheckoutBridge;
    var iosBridge = window.webkit && webkit.messageHandlers && webkit.messageHandlers.CheckoutBridge;
    var isIntentFlow = (CheckoutBridge || iosBridge) && data.type === 'intent';

    if (data.type === 'async' && data.method === 'app' && data.provider === 'cred') {
        $('message-txt').innerText = 'Please complete the payment on the CRED app';
    }

    var lastXhr, lastPollTS, lastPollUrl;
    var threshold = 1000 * 20;{{-- 20 seconds --}}
    var pollRetriesOnError = 5;
    var pollRetriesSoFar = 0;

    onfocus = function(e) {
      if (lastPollTS) {
        {{-- If last XHR was more than threshold seconds ago, abort XHR and start a new poll. --}}
        var timeSince = Date.now() - lastPollTS;

        {{-- Only if its the polling URL --}}
        if (lastPollUrl === request_url && timeSince > threshold) {
          lastXhr.abort();
          fetch(request_url, 1);
          track('ajax_periodic_retry', {
            last: {
              status: lastXhr.status,
              url: lastXhr.url,
              text: lastXhr.responseText
            },
            focus: !!e,
            time: timeSince
          })
        }
      }
    }

    {{-- Keep checking every 1s for hung AJAX --}}
    setInterval(onfocus, 1000);

    {{--
    onpopstate = function() {
      history.pushState(null, null, '/v1/payments/create/checkout/' + data.payment_id);
    }

    //{{- If HTML5 history API exists, only then do this. -}}
    window.history && onpopstate();
    --}}

    function track(name, properties, cb) {
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

        {{-- If a callback is provided, fire it on response headers recieved
             or fire it in 4s anyway
          --}}
        if (cb) {
          call.onreadystatechange = function() { if (call.readyState === 2) { cb() } }
          setTimeout(cb, 4e3);
        }

        call.send('key=MC40OTMwNzgyMDM3MDgwNjI3Nw9YnGzW&data=' +
                 encodeURIComponent(btoa(JSON.stringify(payload))));
      })
    }

    function paymentCallback(data) {
      if (window.CheckoutBridge) {
        CheckoutBridge.oncomplete(JSON.stringify(data));
      } else if (iosBridge) {
        iosBridge.postMessage({
          action: 'success',
          body: data
        });
      } else {
        try { window.opener.onComplete(data) } catch(e){}
        try { (window.opener || window.parent).postMessage(data, '*') } catch(e){}
        setTimeout(close, 999);
      }
    }

    function openIntentUrl(intentUrl, payment_id) {
      if (window.CheckoutBridge) {
        CheckoutBridge.callNativeIntent(intentUrl);
      } else if (iosBridge) {
        iosBridge.postMessage({
          action: 'callNativeIntent',
          body: {
            intent_url: intentUrl,
            payment_id: payment_id
          }
        });
      }
    }

    {{--
      submit form redirects to callback_url
      or, in case of anrdoid app, call CheckoutBridge.oncomplete
    --}}

    var submitted_count = 0;
    function submitForm(response) {
      {{-- track if page not closed after 10s of calling submitForm --}}
      setTimeout(function() {
        track('no_redirect', {
          count: ++submitted_count
        });
        if (submitted_count && !(submitted_count % 2) && submitted_count < 10) {
          submitForm(response);
        }
      }, 10000);
      if (isIntentFlow) {
        paymentCallback(response);
      } else {
        if (response && response.type === 'return') {
          var req = response.request;
          var content = req.content;
          form.action = req.url;
          form.method = req.method;
          form.innerHTML = Object.keys(content)
            .map(function (name) { return '<input name="' + name + '" value="' + content[name] + '">' })
            .join('')
        } else {
          form.action = callback_url;
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
          lastPollUrl = url;
          lastPollTS = Date.now();

          lastXhr = new XMLHttpRequest();
          lastXhr.open('get', url, true);

          lastXhr.onreadystatechange = function() {
            if (lastXhr.readyState === 4 && lastXhr.status) {
              var json;
              try {
                json = JSON.parse(lastXhr.responseText);
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
                    status: lastXhr.status,
                    text: lastXhr.responseText,
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
                    json.error ||
                    json.version === 1
                  ) {
                    return submitForm(json);
                  }
                } catch(e) {
                  return handleAjaxError(e);
                }
              }
              handleAjaxError();
            }
          }
          lastXhr.onerror = handleAjaxError;
          lastXhr.send(null);
        }, timeout || 4000);
      }

      fetchAgain(immediate);
    }

    if (isIntentFlow) {
      var intent_url = data.data.intent_url;
      var payment_id = data.payment_id;

      function initUpiActivity() {
        try {
          openIntentUrl(intent_url, payment_id);
          $('spinner').className = 'hide';
          $('retry-btn').className = 'hide';
          $('message-txt').innerHTML = iosBridge ? "Please accept the request from Razorpay's VPA on your UPI app" : '<b>Redirecting to UPI App</b>Payment will be made to Razorpay\'s VPA';
          window.pollStatus = function(resp) {
            if (!Object.keys(resp).length ||
                /txnId=(undefined|null|)(&|$)/i.test(resp.response) ||
                {{-- For PhonePe (txnId starts with YBL), if bleTxId does not exist, the user cancelled. --}}
                (/txnId=YBL/i.test(resp.response) && Object.keys(resp).indexOf('bleTxId') < 0)
            ) {
              {{-- Cancel if webview is hidden or merchant is shaadi.com --}}
              {{-- Type-checking for resp.isWebviewVisible because it might be undefined --}}
              // if (resp.isWebviewVisible === false || key_id === 'rzp_live_5WqsyF9dNRzsmf') {
                fetchWait(cancel_url);
              // }
            //   $('cancel-btn').className = '';
            //   $('retry-btn').className = '';
            //   $('message-txt').innerHTML = 'Payment did not complete';
            //   $('spinner').className = 'hide';

            $('message-txt').innerHTML = 'Please wait..';
            $('spinner').className = '';
            } else {
              fetchWait(request_url);
            }
          }
        } catch(e) {
          track('android_error', {
            error: e.message
          }, submitForm)
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

    function handleAjaxError(e) {
      var props = {
        text: lastXhr.responseText,
        status: lastXhr.status,
        url: lastPollUrl
      }
      if (e) {
        props.message = e.message;
      }

      {{-- pass redirection callback if its unexpected response error --}}
      track('ajax_error', props, !e && submitForm);

      {{-- retry if its json parsing or network error --}}
      if (e && pollRetriesSoFar < pollRetriesOnError) {
        pollRetriesSoFar++;
        fetch(lastPollUrl);
      }
    }
  </script>
